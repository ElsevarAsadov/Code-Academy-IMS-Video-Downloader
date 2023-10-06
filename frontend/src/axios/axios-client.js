import axios from "axios";

const AXIOSCLIENT = axios.create({
    baseURL: `${import.meta.env.VITE_API_BASE_URL}/api`,
});

AXIOSCLIENT.interceptors.response.use(null, err => {
    //whenever in request user fails due to authorization then remove access_token
    if (err.response.status === 401) {
        localStorage.removeItem("ACCESS_TOKEN")
        localStorage.removeItem("USER")
    }
})

export function sendLoginRequest() {
    window.location.assign(`https://github.com/login/oauth/authorize?client_id=${import.meta.env.VITE_GITHUB_CLIENT_ID}`)
}

export function getAccessToken(code) {
    return AXIOSCLIENT.get(`/get-accessToken?code=${code}`).then((response) => {
        //if user successfully authenticated
        if (response?.data?.access_token) {
            localStorage.setItem("ACCESS_TOKEN", response.data.access_token)
            return true
        }
        return false;
    }).catch((r) => false)
}

export function getUserData(token) {
    return AXIOSCLIENT.get('/get-userData', {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
        }
    }).then(response => {
        return response?.data
    }).catch(err=>{
        if(err?.response?.status === 401) return false;
    })
}

export function saveCookies(id, token, cookies){
    return AXIOSCLIENT.patch(`/save-cookies/${id}`, cookies, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    }).then(response=>{
        if(response?.status === 200) return true;
        return false;
    }).catch(e=>{
        console.log(e)
        return false;
    });

}

export function downloadVideo(token){

}


export default AXIOSCLIENT