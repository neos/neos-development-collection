import React from "react";
import { createRoot } from "react-dom/client";
import VariantsApp from "./Variants/index";

const variantsInformation = JSON.parse(
	document.getElementById("variants-information").innerHTML,
);
const originalInformation = JSON.parse(
	document.getElementById("original-information").innerHTML,
);

const rootElement = document.getElementById("variants-app");
const root = createRoot(rootElement);

root.render(
	<VariantsApp
		variantsInformation={variantsInformation}
		originalInformation={originalInformation}
	/>,
);
