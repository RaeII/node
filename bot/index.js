const fetch = require("node-fetch");
const puppeteer = require('puppeteer');

async function getUser() {
  try {
    const response = await fetch('https://api.adviceslip.com/advice');

    if (!response.ok) {
      throw new Error(`Error! status: ${response.status}`);
    }

    const result = await response.json();
    return result;
  } catch (err) {
    console.log(err);
  }
}

console.log(await getUser());



// (async () => {
//   const browser = await puppeteer.launch({
//     headless: true,
//   });
//   const page = await browser.newPage();
//  // await page.goto('https://translate.google.com.br/?hl=pt-BR');
//  await page.goto('https://translate.google.com.br/?hl=pt-BR');//acessar a pagina
//  await page.type('.er8xn','you are ok?')
//  const delay = ms => new Promise(resolve => setTimeout(resolve, ms))
//  await delay(5000)
//  const price = await page.evaluate(() => {
//     const elements = document.getElementsByClassName('Q4iAWc');
//     return Array.from(elements).map(element => element.innerText); // as you see, now this function returns array of texts instead of Array of elements
//   })
//   console.log(price)
// // console.log(spanName)
// //  let value = await page.$eval('[name="item[user]"]', (input) => {
// //     return input.getAttribute("value")
// //     });
// //  console.log(value)
// //  await page.type('[name="item[pass]"]','pass')
// //  await page.click('.btn')


//  await browser.close();
// })();