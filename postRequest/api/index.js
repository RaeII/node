
const http = require('http')
const data = require('./url.json')
const URL = require('url')
const fs = require('fs')
const path = require('path')

function writeFile(cb){
  fs.writeFile(path.join(__dirname,'url.json'),
                    JSON.stringify(data,null,2),
                    err => {
                      if(err) throw err 

                      cb(data)
                    } 
                )
}

http.createServer((req, res)=>{
  //pega o nome e a url a partir do get
  const {name, url, del} = URL.parse(req.url,true).query //true indica que quero pegar query(get)
  
  //aceitar requisições de outros lugares, corls
  res.writeHead(200, {'Access-Control-Allow-Origin':'*'})
  
  
  //esta na home
  if(!name || !url)
        return res.end(JSON.stringify(data))

     if(del){
         data.urls = data.urls.filter(item => String(item.url) !== String(url))
         
         return writeFile((data)=>{
           res.end(JSON.stringify(data))
         })
          
     }
       data.urls.push({name,url})  
       return writeFile((data)=>{
        res.end(JSON.stringify(data))
      })

}).listen(4000, () => console.log('api is running'))
