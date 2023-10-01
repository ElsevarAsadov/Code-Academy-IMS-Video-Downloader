import axios from "axios";

const AXIOSCLIENT = axios.create({
    baseURL: `${import.meta.env.VITE_API_BASE_URL}`,
});


//
// AXIOSCLIENT.interceptors.request.use((config) => {
//     const accToken = localStorage.getItem("ACCESS_TOKEN");
//     config.headers.Authorization = `Bearer ${accToken}`;
//     return config;
// });
//
// AXIOSCLIENT.interceptors.response.use(
//     (r) => {
//         return r;
//     },
//     (err) => {
//         //unauthorized user for some reason...
//         if (err.response?.status === 400){
//             localStorage.removeItem("ACCESS_TOKEN");
//             window.location.reload();
//         }
//         else{
//             alert(err)
//         }
//         return Promise.reject(err)
//     },
//);


export function sendLoginRequest(){
    alert('login request sented')
    // AXIOSCLIENT.get('')
}


export default AXIOSCLIENT