import axios from 'axios'

// Set base URL
axios.defaults.baseURL = '/api'

// Set default headers
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Add CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}

// Add auth token interceptor
axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Add response interceptor for error handling
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Clear token and redirect to login
            localStorage.removeItem('auth_token')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

export default axios