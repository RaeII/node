
const os = require('os')
const log = require('./logger')
//Funcão será executada a cada 1min
setInterval(()=>{

    const {freemem, totalmem} = os
    const total = parseInt(totalmem() / 1024 / 1024 )
    const mem = parseInt(freemem() / 1024 / 1024)
    const percents = parseInt((mem / total) * 100)
    
    const stats ={
        free: `${mem} MB`,
        total: `${total} MB`,
        usage: `${percents} MB`
    }
   console.clear()
   console.table(stats)
    log('Escrita de arquivo')
    
},1000)

 