import express from "express";
import convertController from "../controllers/convert.js";

const convert = express.Router();

convert.get("/", convertController.convert);

export default convert;