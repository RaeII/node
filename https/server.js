//servidor https
//npm i para recarregar os modulos
//nodemon packge.json vai monitorar toda mudança no arquivo e vai atualizar sem precisar reiniciar
const http = require('http')
const fs = require('fs')
const path = require('path')


//req - pedido
//red - resposta
http.createServer((req, res)=>{
    const file = req.url === '/' ? 'index.html' : req.url
    console.log(file)
    res.end('chegou')

    // if(req.url == '/'){
    //    fs.readFile(path.join(__dirname,'cuonline','index.html'),(err,content)=>{
    //                             if(err) throw err
    //                             res.end(content)
    //                         })
    // }

}).listen(3000, () => console.log('server is running'))
