export const products = [
  { id: 'berry', name: 'Zemeņu krēms', tag: 'Berry best', tone: 'pink', category: 'Piena', price: 7, tile: 0, description: 'Piena šokolāde ar zemeņu krēmu. Salds vasaras mirklis.' },
  { id: 'salt', name: 'Sāls + Olīveļļa', tag: 'Sigma pick', tone: 'purple', category: 'Tumšā', price: 11, tile: 1, description: 'Tumšā šokolāde ar jūras sāli un olīveļļas pieskārienu.' },
  { id: 'matcha', name: 'Matcha Mocha', tag: 'Stay fresh', tone: 'mint', category: 'Baltā', price: 10, tile: 2, description: 'Baltā šokolāde un maiga matcha. Tava zaļā enerģija.' },
  { id: 'caramel', name: 'Caramel Crunch', tag: 'Golden hour', tone: 'yellow', category: 'Piena', price: 10, tile: 3, description: 'Kraukšķīga karamele maigā piena šokolādē.' },
  { id: 'mint', name: 'Main Character Mint', tag: 'Mint edition', tone: 'mint', category: 'Tumšā', price: 10, tile: 4, description: 'Tumšā šokolāde ar svaigu piparmētru raksturu.' },
  { id: 'raspberry', name: 'Slay Strawberry', tag: 'Berry mood', tone: 'pink', category: 'Piena', price: 7, tile: 5, description: 'Ogu garšas sprādziens ar avenēm un piena šokolādi.' },
  { id: 'hazelnut', name: 'Giga-Nut Crunch', tag: 'Extra crunch', tone: 'mint', category: 'Piena', price: 11, tile: 6, description: 'Grauzdēti lazdu rieksti un šokolāde. Kraukšķis katrā kumosā.' },
  { id: 'mango', name: 'Kūstošais mango', tag: 'New drop', tone: 'yellow', category: 'Baltā', price: 12, tile: 7, description: 'Tropisks mango un zīdaini maiga baltā šokolāde.' },
  { id: 'original', name: '67 Original', tag: 'The original', tone: 'yellow', category: 'Tumšā', price: 9, tile: 8, description: 'Bagātīga šokolāde ar apelsīna notīm un sigma enerģiju.' },
]
export const money = value => new Intl.NumberFormat('lv-LV', { style: 'currency', currency: 'EUR' }).format(value)
