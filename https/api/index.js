
const http = require('http')
const data = require('./url.json')
const URL = require('url')
const fs = require('fs')
const path = require('path')

http.createServer((req, res)=>{
  //pega o nome e a url a partir do get
  const {name, url, del} = URL.parse(req.url,true).query //true indica que quero pegar query(get)
  
  //esta na home
  if(!name || !url)
        return res.end(JSON.stringify(data))

     if(del){
         data.urls = data.urls.filter(item => String(item.url) !== String(url))

         return fs.writeFile(path.join(__dirname,'url.json'),
                      JSON.stringify(data,null,2),
                      err => {
                        if(err) throw err 

                        res.end(JASON.stringify({message:"ok"}))
                      } 
                    )

     }
         
             return res.end('create')
}).listen(4000, () => console.log('api is running'))
