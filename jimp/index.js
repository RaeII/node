const Jimp = require('jimp')

async function main(){
   let font = await Jimp.loadFont(Jimp.FONT_SANS_16_BLACK)
   let imgBack = await Jimp.read('img/back.jpg')
   imgBack.print(font, 10, 20,'Israel Zeferino')
   imgBack.write('cotacao.png')


}
main()

