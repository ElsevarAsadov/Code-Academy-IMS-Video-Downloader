//ICONS START
import EmojiEmotionsIcon from '@mui/icons-material/EmojiEmotions';
import LoginIcon from '@mui/icons-material/Login';
import ArrowUpwardIcon from '@mui/icons-material/ArrowUpward';
import CloseIcon from '@mui/icons-material/Close';
//ICONS END

//REQUESTS START
import {getAccessToken, getUserData, sendLoginRequest} from "./axios/axios-client.js";
//REQUESTS END


import * as React from 'react';
import {Alert, Button, Dialog, DialogTitle, TextField} from "@mui/material";


function App() {
    const [isLogged, setLogged] = React.useState(false)
    const [userData, setUserData] = React.useState([]);
    const [bottomSectionOpened, setBottomSectionOpened] = React.useState(false)
    const [askCookie, setAskCookie] = React.useState(false);
    const animationCtx = React.useRef();
    const animationRoot = React.useRef()
    const mounted = React.useRef(false)

    const TOKEN = localStorage.getItem("ACCESS_TOKEN")

    React.useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');

        !TOKEN && code && getAccessToken(code).then(r => {
            if (!r) {
                setLogged(false);
            } else {
                localStorage.setItem("ACCESS_TOKEN", r['access_token'])
                setLogged(true);
            }
        })
    }, [])


    React.useEffect(() => {
        //userData.map(i => console.log(i))
        console.log(userData)
    }, [userData])

    React.useEffect(() => {
        getUserData().then(r => {
            console.log(r)
            if (r.data) {
                setUserData(r.data);
                setLogged(true)
            }
        }).catch(e => {
            console.log(e)
        })
    }, [isLogged])

    React.useEffect(() => {
        animationCtx.current = gsap.context(() => {
        }, animationRoot)

        return () => {
            animationCtx.current.revert()
        }
    }, [animationCtx])

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
            <Dialog className={'w-screen'} open={askCookie} onClose={() => setAskCookie(false)}>
                <DialogTitle className={'relative'}>
                    <p>Cookie Datalari Daxil Edin</p>
                    <CloseIcon className={'absolute top-[6px] right-[6px] hover:cursor-pointer'}
                               onClick={() => setAskCookie(false)}/>
                </DialogTitle>
                <div className='w-screen  max-w-full flex flex-col items-center gap-8 p-12'>
                    <TextField
                        sx={{display: 'block'}}
                        defaultValue={''}
                        InputLabelProps={{
                            shrink: true,
                        }}
                        label="PHPSESSID:"
                        variant={"standard"}/>
                    <TextField sx={{display: 'block'}}
                               InputLabelProps={{
                                   shrink: true,
                               }}
                               defaultValue={''}
                               className="block"
                               label="login_token:"
                               variant={"standard"}/>
                    <TextField sx={{display: 'block'}}
                               InputLabelProps={{
                                   shrink: true,
                               }}
                               defaultValue={''}
                               className="block"
                               label="cookie_microstats_visibility:"
                               variant={"standard"}/>
                    <Alert severity="warning">CA LMS platformunun backend <a className={'text-blue-500'}
                                                                             href={'https://en.wikipedia.org/wiki/Implementation'}
                                                                             target={"_blank"}>implementasiya</a>-sina
                        gore bu <a href={"https://en.wikipedia.org/wiki/HTTP_cookie"}
                                   target={"_blank"}
                                   className={'text-blue-500'}>cookie</a>-ler yanliz ve yanlis maksumum 2 gun
                        gecerlidir!</Alert>
                </div>
            </Dialog>
            <div id={"main"} className={'w-full h-full'}>
                <nav className={'h-[10%]   flex items-center justify-end p-10 '}>
                    {isLogged ?
                        <div className={"hover:cursor-pointer flex items-center  gap-4"}
                             onClick={() => window.location.assign(`https://github.com/${userData.login}`)}>
                            <p className={'text-white'}>{userData.login}
                            </p>
                            <img
                                className={'inline aspect-square w-[50px] rounded-[50%]'}
                                src={userData.avatar_url}
                            />
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
                        <Button sx={{
                            ":hover": {
                                backgroundColor: 'black',
                                color: 'white'
                            }
                        }}
                                variant={'outlined'}


                                onClick={() => {
                                    if (!isLogged) {
                                        setAskCookie(true)
                                    }
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
                <div id={'manual'} className={'hidden justify-center items-center'}>
                    <CloseIcon fontSize={"large"} className={'absolute top-[25px] right-[25px] hover:cursor-pointer'}
                               onClick={() => setBottomSectionOpened(false)}/>

                    <iframe className={'align-center'} width="560" height="315"
                            src="https://www.youtube.com/embed/5NV6Rdv1a3I?si=xzW-KYGLwU4JTIXG"
                            title="YouTube video player" frameBorder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowFullScreen></iframe>
                </div>
            </div>
        </div>
    )
}

export default App
