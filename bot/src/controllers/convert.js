import puppeteer from 'puppeteer';
import Jimp from 'jimp';
import short from 'shortid';
import fs from 'fs/promises';

class convertController {

  convert = async (req, res) => {

    
    const html = `<aside class="aside-panel">
          <div class="content-panel">
              <div>
                  <p class="pre-title">Te falei que é easy 😉</p>
                  <h1>Faça bom uso
                      do takip e ganhe eficiência</h1>
                      <div class="infos">
                          <p>#comunidade #registroDeMarca</p>
                          <p>Você está usando a versão freemium.</p>
                          <div>
                              <p>Se estiver gostando,</p> 
                              <p><u>compartilhe</u> com seus colegas.</p>
                          </div>
                      </div>
                  </div>
            
              <div class="footer-img-aside">
                  <img src="logo.svg" alt="logo">
              </div>       
          </div>
                 </aside>`;

    (async () => {
            
        const width = 1600, height = 1040;
        const option = { headless: true, slowMo: true, args: [`--window-size=${width},${height}`] };    
        const browser = await puppeteer.launch(option);
        const page = await browser.newPage();
        const vp = {width: 1600, height: 1040};
        await page.setViewport(vp);
  
        await page.goto('http://localhost/node/bot/src/convert/');//acessa a pagina
  
  
        async function setSelectVal(sel, val) {
            page.evaluate((data) => {
            return document.querySelector(data.sel).value = data.val
            }, {sel, val})
        }
        await setSelectVal('#html-content', html)

        await page.click('#btn')
  
        const dimension = await page.evaluate(async () => {
                const values = {}
                values.height = await document.querySelector('#height').value 
                values.width =  await document.querySelector('#width').value
                return values
            })
        const imageid = Date.now()+short();
        await page.screenshot({path:`./src/images/image_${imageid}.png`})

        await browser.close();
        
        const base64 = await this.cut(dimension.width, dimension.height,imageid)


        await this.deleteImage(imageid)
  
        return res.status(200).json({image64 : base64});
    })()

  };

  cut = async (w,h,id) => {
    w = Number(w) 
    h = Number(h)
    const image = await Jimp.read(`./src/images/image_${id}.png`);
    return image.crop(0, 0, w, h)
               .getBase64Async(Jimp.AUTO).then(base64 =>base64)
               .catch(e => console.log(e))
  }

  deleteImage = async (id) => {
    await fs.unlink(`./src/images/image_${id}.png`, function (err) {
      if (err) return console.log(err);
      console.log('image cotation delete sucess');
    });
  }
   
}

export default new convertController();