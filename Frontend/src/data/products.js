const currencyFormatter = new Intl.NumberFormat('lv-LV', { style: 'currency', currency: 'EUR' })

export const money = value => currencyFormatter.format(value)
