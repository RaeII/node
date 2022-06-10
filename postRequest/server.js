//servidor https
//npm i para recarregar os modulos
//nodemon packge.json vai monitorar toda mudança no arquivo e vai atualizar sem precisar reiniciar(trocar start no json)
const http = require('http')
const fs = require('fs')
const path = require('path')


//req - pedido
//res - resposta
//todos os arquivos são recebidos por uma requisição e passando pelo file
http.createServer((req, res)=>{
    const file = req.url === '/' ? 'index.html' : req.url
    const filePath = path.join(__dirname,'public',file)
    const extName = path.extname(filePath)
    
    const allowedFileTypes = ['.html','.css','.js','.png','.jpg','.jpeg','.svg']
    const allowed = allowedFileTypes.find(item => item == extName)

    if(!allowed) return

       console.log(req.url) 
       fs.readFile(filePath,(err,content)=>{
                                if(err) throw err
                                res.end(content) 
                            })
   

}).listen(3000, () => console.log('server postRequest'))
