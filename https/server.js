//servidor https
const http = require('http')
const fs = require('fs')
const path = require('path')
const { dir } = require('console')

//req - pedido
//red - resposta
http.createServer((req, res)=>{

    const file = req.url === '/' ? 'index.html' : req.url
    console.file()

    // if(req.url == '/'){
    //    fs.readFile(path.join(__dirname,'cuonline','index.html'),(err,content)=>{
    //                             if(err) throw err
    //                             res.end(content)
    //                         })
    // }

}).listen(5000, () => console.log('server is running'))
