import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

async function request(path, options = {}) {
  try {
    const response = await api.request({ url: path, ...options })
    return response.data
  } catch (error) {
    const message = error.response?.data?.error || error.response?.data?.message || 'Store request failed.'
    throw new Error(message)
  }
}

export function getProducts() {
  return request('/products')
}

export function saveProduct(product) {
  return request('/products', {
    method: 'POST',
    data: product,
  })
}

export function saveOrder(order) {
  return request('/orders', {
    method: 'POST',
    data: order,
  })
}