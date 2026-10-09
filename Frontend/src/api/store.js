import axios from 'axios'
const api = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' }, withCredentials: true, timeout: 15000 })
async function request(path, options = {}) {
  try { return (await api.request({ url: path, ...options })).data }
  catch (cause) {
    const data = cause.response?.data
    const message = data?.errors ? Object.values(data.errors).flat().join(' ') : data?.error || data?.message || 'Neizdevās savienoties ar veikalu. Pārbaudi, vai backend ir palaists.'
    const error = new Error(message)
    error.status = cause.response?.status
    throw error
  }
}
async function mutate(path, data) {
  const { token } = await request('/auth/csrf')
  return request(path, { method: 'POST', data, headers: { 'X-CSRF-TOKEN': token } })
}
export const getProducts = () => request('/products')
export const getUser = () => request('/auth/user')
export const login = credentials => mutate('/auth/login', credentials)
export const register = details => mutate('/auth/register', details)
export const logout = () => mutate('/auth/logout')
export const checkout = order => mutate('/checkout', order)
export const saveProduct = product => request('/products', { method: 'POST', data: product })
export const saveOrder = order => request('/orders', { method: 'POST', data: order })
