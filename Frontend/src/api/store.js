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
export const validateCoupon = (code, productIds) => request('/coupons/validate', { params: { code, productIds } })
export const getAdminCoupons = () => request('/admin/coupons')
export const createAdminCoupon = coupon => mutate('/admin/coupons', coupon)
export const revokeAdminCoupon = id => request(`/admin/coupons/${id}`, { method: 'DELETE' })
function productFormData(product) {
  const data = new FormData()
  for (const [key, value] of Object.entries(product)) {
    if (value !== undefined && value !== null) data.append(key, value)
  }
  data.append('imageSelected', product.image ? '1' : '0')
  return data
}
export const saveProduct = product => mutate('/products', productFormData(product))
export async function updateProduct(id, product) {
  const data = productFormData(product)
  data.append('_method', 'PUT')
  const { token } = await request('/auth/csrf')
  return request(`/products/${id}`, { method: 'POST', data, headers: { 'X-CSRF-TOKEN': token } })
}
export const deleteProduct = async id => {
  const { token } = await request('/auth/csrf')
  return request(`/products/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token } })
}
