const EventEmitter = require('events')
const emitter = new EventEmitter()

const fs = require('fs')
const path = require('path')


//emitter sempre vai ficar olhando para a funcao, quando for emitir log vai aparecer a msg
emitter.on('log',(message)=>{
    fs.appendFile(path.join(__dirname,'log.txt'),message,err=>{
        if(err) throw err
    })
})

function log(message) {
   emitter.emit('log',message)
}

//exportando a função log
module.exports = log

