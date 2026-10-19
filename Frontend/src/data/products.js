export const money = value => new Intl.NumberFormat('lv-LV', { style: 'currency', currency: 'EUR' }).format(value)
