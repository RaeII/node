import { Router } from "express";
import convert from "./convert.js";

const apiRouter = Router();

apiRouter.use("/convert", convert);

export default apiRouter;