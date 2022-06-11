const http = require('http')
const data = require('./url.json')
const URL = require('url')
const fs = require('fs')
const path = require('path')
const express = require('express')
const app = express();
var bodyParser = require('body-parser');
app.use(express.static('.'))
app.use(bodyParser.urlencoded({extended:true}))
app.use(bodyParser.json())
let allowCrossDomain = function(req, res, next) {
  res.header('Access-Control-Allow-Origin', "*");
  res.header('Access-Control-Allow-Headers', "*");
  next();
}
app.use(allowCrossDomain);

function writeFile(cb){
  fs.writeFile(path.join(__dirname,'url.json'),
                    JSON.stringify(data,null,2),
                    err => {
                      if(err) throw err 

                      cb(data)
                    } 
                )
}

   app.get('/users', (req, res, nex)=>{
     res.send(JSON.stringify(data))
   })

   app.get('/user', (req, res, nex)=>{
    const {id} = URL.parse(req.url,true).query
    user = data.urls.filter(item => parseInt(item.id) == parseInt(id))
            
              return res.end(JSON.stringify(user[0]))
             
  })

app.post('/register', function(req, res){
            var dados = JSON.parse(JSON.stringify(data.urls))
           var idNew = parseInt(dados[dados.length - 1].id) + 1
           req.body =({...req.body,id:idNew})

             data.urls.push(req.body)  
             writeFile((data)=>{
             res.end(JSON.stringify(data))
            })

  res.send(
    data
  )

});

app.listen(4500,()=>console.log('api-post is running')) 







// http.createServer((req, res)=>{
//   //pega o nome e a url a partir do get
//   const {name, url, del} = URL.parse(req.url,true).query //true indica que quero pegar query(get)
  
//   //aceitar requisições de outros lugares, corls
//   res.writeHead(200, {'Access-Control-Allow-Origin':'*'})
  
  
//   //esta na home
//   if(!name || !url)
//         return res.end(JSON.stringify(data))

//      if(del){
//          data.urls = data.urls.filter(item => String(item.url) !== String(url))
         
//          return writeFile((data)=>{
//            res.end(JSON.stringify(data))
//          })
          
//      }
//        data.urls.push({name,url})  
//        return writeFile((data)=>{
//         res.end(JSON.stringify(data))
//       })

// }).listen(4000, () => console.log('api-post is running'))
