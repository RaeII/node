import app from './src/loaders/express.js';
import colors from 'colors/safe.js';

app.listen(5000,()=>console.log(
      colors.yellow.bold(
      colors.rainbow(' -===============================-\n'),
                     'API BOTIMAGE RUNNING\n Server listening on port:5000\n'),
      colors.rainbow('-===============================-'))) 





