import axios from 'axios'
const statusMessages = {
  401: 'Sesija ir beigusies. Pieslēdzies vēlreiz.',
  403: 'Šī darbība nav atļauta šim kontam. Pārbaudi konta piekļuvi.',
  413: 'Augšupielāde pārsniedz servera atļauto izmēru. Izvēlies mazāku attēlu.',
  419: 'Sesija ir beigusies. Pārlādē lapu un pieslēdzies vēlreiz.',
}
const api = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' }, withCredentials: true, timeout: 15000 })
async function request(path, options = {}) {
  try { return (await api.request({ url: path, ...options })).data }
  catch (cause) {
    const data = cause.response?.data
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
export const saveAddress = address => mutate('/auth/address', { address }, 'PUT')
export const getShipments = (admin = false) => request(admin ? '/admin/shipments' : '/shipments')
export const updateShipment = (id, data, admin = false) => mutate(`${admin ? '/admin' : ''}/shipments/${id}`, data, 'PATCH')
export const getUser = () => request('/auth/user')
export const login = credentials => mutate('/auth/login', credentials)
export const register = details => mutate('/auth/register', details)
export const logout = () => mutate('/auth/logout')
export const updateProfile = details => mutate('/auth/profile', details)
export const deleteAccount = details => mutate('/auth/account', details, 'DELETE')
export const checkout = order => mutate('/checkout', order)
export const validateCoupon = (code, productIds) => request('/coupons/validate', { params: { code, productIds } })
export const getAdminCoupons = () => request('/admin/coupons')
export const createAdminCoupon = coupon => mutate('/admin/coupons', coupon)
export const revokeAdminCoupon = id => mutate(`/admin/coupons/${id}`, undefined, 'DELETE')
export const saveProduct = product => mutate('/products', product)
export const updateProduct = (id, product) => mutate(`/products/${id}`, product, 'PUT')
export async function uploadProductImage(id, product, image) {
  const data = new FormData()
  for (const key of ['name', 'price', 'stock', 'description', 'tone', 'tag']) {
    const value = product[key]
    if (value !== undefined && value !== null) data.append(key, value)
  }
  data.append('image', image)
  data.append('imageSelected', '1')
  data.append('_method', 'PUT')
  return mutate(`/products/${id}`, data)
}
export const deleteProduct = id => mutate(`/products/${id}`, undefined, 'DELETE')
