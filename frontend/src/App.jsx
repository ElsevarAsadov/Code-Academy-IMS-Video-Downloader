//ICONS START
import EmojiEmotionsIcon from '@mui/icons-material/EmojiEmotions';
import LoginIcon from '@mui/icons-material/Login';
import ArrowUpwardIcon from '@mui/icons-material/ArrowUpward';
import CloseIcon from '@mui/icons-material/Close';
//ICONS END

//REQUESTS START
import {getAccessToken, getUserData, saveCookies, sendLoginRequest} from "./axios/axios-client.js";
//REQUESTS END


import * as React from 'react';
import {
    Alert,
    Button,
    Dialog,
    DialogTitle,
    TextField,
    CircularProgress,
    Snackbar,
    SnackbarContent,
    FormControl
} from "@mui/material";


function App() {
    /*
    isLogged State can be 3 state:
    'true' -> user is logged in
    'false' -> user is NOT logged in
    'waiting' -> user requesting server to get state (SHOW LOADING SCREEN)
    * */
    const [isLogged, setLogged] = React.useState(false)
    const [loadingState, setLoadingState] = React.useState(false);
    const [userData, setUserData] = React.useState([]);
    const [bottomSectionOpened, setBottomSectionOpened] = React.useState(false)
    const [snackBarVisibility, setSnackBarVisibility] = React.useState(false)
    const [snackBarMessage, setSnackBarMessage] = React.useState('');
    const [askCookie, setAskCookie] = React.useState(false);
    const animationCtx = React.useRef();
    const animationRoot = React.useRef()
    const mounted = React.useRef(false)

    //FORM STATES
    const [phpsession, setPhpsession] = React.useState('')
    const [loginToken, setLoginToken] = React.useState('')
    const [microstats, setMicrostats] = React.useState('')

    //FORM STATES


    const TOKEN = localStorage.getItem("ACCESS_TOKEN")


    const modalTexts = {
        'save-cookie': 'Melumat Servere Gonderilir',
        'logging-in': 'Serverden Cavab Gozlenilir',
    }

    React.useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        //try to get access token
        if (code && !TOKEN) {
            //set waiting state
            setLoadingState('logging-in')
            getAccessToken(code).then(r => {
                setLogged(r)
                setLoadingState(false)
            })
        }
    }, [])


    React.useEffect(() => {
        //Assume if there is token then user is logged(anyway token will be removed if one request to server responses 401)
        if (TOKEN) {
            setLogged(true);
        }
    }, [userData])

    React.useEffect(() => {
        //if u ser is logged but while requesting server if server sends 401 then it means token is not valid
        if (isLogged && TOKEN) getUserData(TOKEN).then(data => {
            data !== false ? setUserData(data) : setLogged(false)
        });
    }, [isLogged])

    //GSAP ANIMATION CONTEXT (DO NOT TOUCH IT)
    React.useEffect(() => {
        animationCtx.current = gsap.context(() => {
        }, animationRoot)

        return () => {
            animationCtx.current.revert()
        }
    }, [animationCtx])

    //GSAP ANIMATIONS.
    React.useEffect(() => {
        if (mounted.current === false) {
            mounted.current = true
            return
        }
        animationCtx.current.add(() => {
            const tl = gsap.timeline();
            if (bottomSectionOpened === true) {
                tl.to('#main', {
                    opacity: 0,
                })
                tl.to('#bottom-icon-container', {
                    opacity: 0,
                    display: 'none',
                })
                tl.to('#bottomSection', {
                    top: 0,
                    borderRadius: 0,
                })
                tl.to('#manual', {
                    display: 'flex',
                    opacity: 0,
                    duration: 0,
                })
                tl.to('#manual', {
                    opacity: 1,
                })
            } else {
                tl.to('#manual', {
                    opacity: 0,
                })
                tl.to('#manual', {
                    display: 'none',
                    opacity: 1,
                    duration: 0,
                })
                tl.to('#bottomSection', {
                    top: '80%',
                    borderRadius: '12%',
                })
                tl.to('#bottom-icon-container', {
                    opacity: 1,
                    display: 'block',
                })
                tl.to('#main', {
                    opacity: 1,
                })

            }
        })
    }, [bottomSectionOpened])

    return (
        <div ref={animationRoot} className={'h-screen w-screen overflow-hidden relative'}>

            <Snackbar open={snackBarVisibility} autoHideDuration={6000}>
                <Alert severity="success" sx={{width: '100%', color: 'black'}}>
                    <p>{snackBarMessage}</p>
                </Alert>
            </Snackbar>

            {/*Logging Info*/}
            <Dialog open={loadingState} className={'w-screen'}>
                <div className={'flex justify-center items-center w-[30vw] h-[30vh] gap-4'}>
                    <CircularProgress color={'primary'}/>
                    <p>{modalTexts[loadingState]}</p>
                </div>
            </Dialog>
            {/*Logging Info*/}

            {/*Cookie Modal*/}
            <Dialog className={'w-screen'} open={askCookie} onClose={() => setAskCookie(false)}>
                <DialogTitle className={'relative'}>
                    <p>Cookie Datalari Daxil Edin</p>
                    <CloseIcon className={'absolute top-[6px] right-[6px] hover:cursor-pointer'}
                               onClick={() => setAskCookie(false)}/>
                </DialogTitle>
                <form onSubmit={(e) => {
                    e.preventDefault()

                    setLoadingState('save-cookie')
                    const cookies = {
                        'PHPSESSID': phpsession,
                        'login_token': loginToken,
                        'cookie_microstats_visibility': microstats,
                    }
                    console.log(cookies)
                    const token = localStorage.getItem('ACCESS_TOKEN')
                    if (token && userData?.id) {
                        saveCookies(userData.id, token, cookies).then((r) => {
                            setLoadingState(false)
                            setSnackBarVisibility(true)
                            setSnackBarMessage(`Data ugurla servere gonderildi`)
                            setTimeout(() => setSnackBarVisibility(false), 5000)
                        })
                    }
                }}>
                    <div className='w-screen  max-w-full flex flex-col items-center gap-8 p-12'>
                        <TextField
                            sx={{display: 'block'}}
                            defaultValue={''}
                            InputLabelProps={{
                                shrink: true,
                            }}
                            label="PHPSESSID:"
                            variant={"standard"}
                            onChange={(e) => setPhpsession(e.target.value)}
                            required
                        />
                        <TextField sx={{display: 'block'}}
                                   InputLabelProps={{
                                       shrink: true,
                                   }}
                                   defaultValue={''}
                                   className="block"
                                   label="login_token:"
                                   variant={"standard"}
                                   onChange={(e) => setLoginToken(e.target.value)}
                                   required
                        />
                        <TextField sx={{display: 'block'}}
                                   InputLabelProps={{
                                       shrink: true,
                                   }}
                                   defaultValue={''}
                                   className="block"
                                   label="cookie_microstats_visibility:"
                                   variant={"standard"}
                                   onChange={(e) => setMicrostats(e.target.value)}
                                   required
                        />
                        <Button type={'submit'}
                                variant={'contained'}>Yadda Saxla</Button>

                    </div>
                    <Alert sx={{margin: '5%'}} severity="warning">CA LMS platformunun backend <a className={'text-blue-500'}
                                                                             href={'https://en.wikipedia.org/wiki/Implementation'}
                                                                             target={"_blank"}>implementasiya</a>-sina
                        gore bu <a href={"https://en.wikipedia.org/wiki/HTTP_cookie"}
                                   target={"_blank"}
                                   className={'text-blue-500'}>cookie</a>-ler yanliz ve yanlis maksumum 2 gun
                        gecerlidir!</Alert>
                </form>
            </Dialog>
            {/*Cookie Modal*/}

            {/*Website Content*/}
            <div id={"main"} className={'w-full h-full'}>
                <nav className={'h-[10%]   flex items-center justify-end p-[25px_15px] '}>
                    {isLogged && userData ?
                        <div className={"hover:cursor-pointer flex items-center  gap-4"}>
                            <a target={'_blank'} href={`https://github.com/${userData.login}`}>
                                <p className={'text-white'}>{userData.login}
                                </p>
                            </a>
                            <a target={'_blank'} href={`https://github.com/${userData.login}`}>
                                <img
                                    className={'inline aspect-square w-[50px] rounded-[50%]'}
                                    src={userData.avatar_url}
                                />
                            </a>
                        </div>
                        :
                        <Button variant={'contained'} onClick={() => sendLoginRequest()}>Qeydiyyatdan
                            Kec<LoginIcon/></Button>
                    }
                </nav>
                <main className={'flex items-center justify-center h-[90%] p-3'}>
                    <div
                        className={'flex items-stretch gap-2 w-full max-w-[600px] justify-center bg-white p-12 rounded-lg'}>
                        <TextField color={'primary'} fullWidth label="Video Linki"/>
                        <Button variant={'contained'}
                                onClick={() => {
                                    setAskCookie(true)
                                }}
                        >Endir
                        </Button>
                    </div>
                </main>
            </div>
            <div

                id={'bottomSection'}
                className={'absolute h-full top-[80%] rounded-[12%] flex justify-center p-3 bg-black w-full text-white'}>
                <div id={'bottom-icon-container'} className={'text-center hover:cursor-pointer'}
                     onClick={() => setBottomSectionOpened(true)}>
                    <br/>
                    <ArrowUpwardIcon fontSize={'large'} className={'animate-bounce'}/>
                    <br/>
                    <p className={'text-xs'}>Nece Endireceyimi Bilmirem</p>
                </div>
                <div id={'manual'} className={'hidden justify-center items-center '}>
                    <CloseIcon fontSize={"large"} className={'absolute top-[25px] right-[25px] hover:cursor-pointer'}
                               onClick={() => setBottomSectionOpened(false)}/>

                    <iframe className={'align-center w-[100%] aspect-video'} width="560" height="315"
                            src="https://www.youtube.com/embed/5NV6Rdv1a3I?si=xzW-KYGLwU4JTIXG"
                            title="YouTube video player" frameBorder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowFullScreen></iframe>
                </div>
            </div>
            {/*Website Content*/
            }
        </div>
    )
}

export default App
