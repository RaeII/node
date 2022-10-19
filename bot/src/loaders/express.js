
import express from 'express'
import bodyParser from 'body-parser';
const app = express();
app.use(express.static('.'))
app.use(bodyParser.urlencoded({extended:true}))
app.use(bodyParser.json())
import apiRouter from "../api/index.js";
let allowCrossDomain = function(req, res, next) {
  res.header('Access-Control-Allow-Origin', "*");
  res.header('Access-Control-Allow-Headers', "*");
  next();
 }
 app.use(allowCrossDomain);

 app.use("/api/v1", apiRouter);

  app.use((req, res , next) => {
    const err = new Error("Not Found");
    err["status"] = 404;
    next(err);
  });

  /// error handlers
  app.use(((err, req, res, next) => {
    /**
     * Handle 401 thrown by express-jwt library
     */
    if (err.name === "UnauthorizedError") {
      return res.status(err.status).send({ message: err.message }).end();
    }
    return next(err);
  }) );

  app.use(((err, req, res) => {
    res.status(err.status || 500);
    res.json({
      errors: {
        message: err.message,
      },
    });
  }) );


  export default app 
