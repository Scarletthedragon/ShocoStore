import axios from 'axios'
const api = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' }, withCredentials: true, timeout: 15000 })
async function request(path, options = {}) {
  try { return (await api.request({ url: path, ...options })).data }
  catch (cause) {
    const data = cause.response?.data
    const statusMessages = {
      401: 'Sesija ir beigusies. Pieslēdzies vēlreiz.',
      403: 'Nav piekļuves. Pieslēdzies ar administratora kontu un pārbaudi, vai e-pasts ir apstiprināts.',
      413: 'Augšupielāde pārsniedz servera atļauto izmēru. Izvēlies mazāku attēlu.',
      419: 'Sesija ir beigusies. Pārlādē lapu un pieslēdzies vēlreiz.',
    }
    const message = data?.errors ? Object.values(data.errors).flat().join(' ') : statusMessages[cause.response?.status] || data?.error || data?.message || 'Neizdevās savienoties ar veikalu. Mēģini vēlreiz.'
    const error = new Error(message)
    error.status = cause.response?.status
    throw error
  }
}
async function mutate(path, data, method = 'POST') {
  const { token } = await request('/auth/csrf')
  return request(path, { method, data, headers: { 'X-CSRF-TOKEN': token } })
}
export const getProducts = () => request('/products')
export const getUser = () => request('/auth/user')
export const login = credentials => mutate('/auth/login', credentials)
export const register = details => mutate('/auth/register', details)
export const logout = () => mutate('/auth/logout')
export const updateProfile = details => mutate('/auth/profile', details)
export const deleteAccount = details => mutate('/auth/account', details, 'DELETE')
export const resendVerification = () => mutate('/auth/email/resend')
export const checkout = order => mutate('/checkout', order)
export const validateCoupon = (code, productIds) => request('/coupons/validate', { params: { code, productIds } })
export const getAdminCoupons = () => request('/admin/coupons')
export const createAdminCoupon = coupon => mutate('/admin/coupons', coupon)
export const revokeAdminCoupon = id => mutate(`/admin/coupons/${id}`, undefined, 'DELETE')
function productFormData(product) {
  const data = new FormData()
  for (const [key, value] of Object.entries(product)) {
    if (value !== undefined && value !== null) data.append(key, value)
  }
  data.append('imageSelected', product.image ? '1' : '0')
  return data
}
export const saveProduct = product => mutate('/products', product)
export const updateProduct = (id, product) => mutate(`/products/${id}`, product, 'PUT')
export async function uploadProductImage(id, product, image) {
  const data = productFormData({ ...product, image })
  data.append('_method', 'PUT')
  return mutate(`/products/${id}`, data)
}
export const deleteProduct = id => mutate(`/products/${id}`, undefined, 'DELETE')
