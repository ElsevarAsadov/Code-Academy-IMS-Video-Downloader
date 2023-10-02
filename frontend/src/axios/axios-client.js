import axios from "axios";

const AXIOSCLIENT = axios.create({
    baseURL: `${import.meta.env.VITE_API_BASE_URL}/api`,
});


export function sendLoginRequest() {
    window.location.assign(`https://github.com/login/oauth/authorize?client_id=${import.meta.env.VITE_GITHUB_CLIENT_ID}`)
}

export async function getAccessToken(code) {
    try {
        const response = await AXIOSCLIENT.get(`/get-accessToken?code=${code}`);

        return response.data

    } catch (error) {
        console.error(error, 'r');
    }
}

export async function getUserData() {
    const token = localStorage.getItem("ACCESS_TOKEN")
    try {
        const response = await AXIOSCLIENT.get('/get-userData', {
            headers: {
                'Authorization': `Bearer ${token}`,
            }
        })
        return response

    } catch (e) {
        console.error(e)
        if(e.response.data.error)
            localStorage.removeItem("ACCESS_TOKEN")
    }
}


export default AXIOSCLIENT