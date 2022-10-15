import puppeteer from 'puppeteer';
import Jimp from 'jimp';


    const cut = async () => {
        const image = await Jimp.read('./src/images/print.png');
        image.crop(600, 20, 1000, 500, function(err){
            if (err) throw err;
          })
          .write('./src/images/print.png');
    }
    

    (async () => {
            
        const width = 1600, height = 1040;
        const option = { headless: true, slowMo: true, args: [`--window-size=${width},${height}`] };    
        const browser = await puppeteer.launch(option);
        const page = await browser.newPage();
        const vp = {width: 1600, height: 1040};
        await page.setViewport(vp);

        const navigationPromise = page.waitForNavigation();

        await page.goto('http://localhost/takip-lp-valid/src/pages/collision_analysis.html');//acessar a pagina

        const delay = ms => new Promise(resolve => setTimeout(resolve, ms))
        await delay(3000)
    
        await page.screenshot({path:'./src/images/print.png'})
        await browser.close();

        await cut()
    })()

        // console.log(spanName)
        //  let value = await page.$eval('[name="item[user]"]', (input) => {
        //     return input.getAttribute("value")
        //     });
        //  console.log(value)
        //  await page.type('[name="item[pass]"]','pass')
        //  await page.click('.btn')


    
        // })();