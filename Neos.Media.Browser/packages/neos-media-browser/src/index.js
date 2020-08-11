import React from 'react';
import ReactDOM from 'react-dom';
import VariantsApp from "./Variants/index";

const variantsInformation = JSON.parse(document.getElementById('variants-information').innerHTML);
const originalInformation = JSON.parse(document.getElementById('original-information').innerHTML);

ReactDOM.render(<VariantsApp variantsInformation={variantsInformation} originalInformation={originalInformation} />, document.getElementById('variants-app'));
