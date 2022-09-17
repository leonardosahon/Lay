/**!
 * @fileOverview OsAi Minified JS Syntax (OMJ$)
 * @author Osahenrumwen Aigbogun
 * @version 2.0.0
 * @since 23/11/2019
 * @modified 18/01/2022
 * @license
 * Copyright (c) 2019 Osai LLC | osaitech.dev/about.
 *
 */
"use strict";

const $win = window;

const $doc = document;

const $obj = Object;

const $web = navigator;

const $loc = $win.location;

let $store; try{ $store = $win.localStorage } catch (e) {}

const $isInt = str => isNaN(str) ? str : parseInt(str);

const $end = list => list[list.length - 1];

const $omjsError = (component, error, throwError = false, ...others) => {
    console.info("%cOMJ$ ERR :: `" + component + "` ERROR", "background: #e00; color: #fff; padding: 3px;");
    if (others) console.warn(...others);
    console.trace("%c" + error, "background: #fff3cd; color: #1d2124; padding: 2px;");
    if (throwError) throw Error("OMJ$ ERR Thrown");
};

const $id = (elementID, parent = $doc) => {
    try {
        return parent.getElementById(elementID);
    } catch (e) {
        $omjsError("$id", e, true);
    }
};

const $sel = (elementSelector, parent = $doc) => {
    try {
        return parent.querySelector(elementSelector);
    } catch (e) {
        $omjsError("$sel", e, true);
    }
};

const $sela = (elementSelector, parent = $doc) => {
    try {
        return parent.querySelectorAll(elementSelector);
    } catch (e) {
        $omjsError("$sela", e, true);
    }
};

const $tag = (elementTag, parent = $doc) => {
    try {
        return parent.getElementsByTagName(elementTag);
    } catch (e) {
        $omjsError("$tag", e, true);
    }
};

const $name = (elementName, parent = $doc) => {
    try {
        return parent.getElementsByName(elementName);
    } catch (e) {
        $omjsError("$name", e, true);
    }
};

const $cls = (elementClass, parent = $doc) => {
    try {
        return parent.getElementsByClassName(elementClass);
    } catch (e) {
        $omjsError("$cls", e, true);
    }
};

const $on = (element, event, listener, ...options) => {
    let option = options[0] ?? "on";
    try {
        let addListener = (listenerElement, index) => {
            let listenerFn = e => listener(e, $type(element) === "Array" ? element[index] : element, index, ...options);
            if (option === "on") {
                let eventList = event.split(",");
                if (eventList.length > 1) eventList.forEach((listen => listenerElement["on" + listen] = listenerFn)); else listenerElement["on" + event] = listenerFn;
            } else if (option === "remove" || option === "del") {
                listenerElement.removeEventListener(event, listenerFn, false);
                listenerElement["on" + event] = () => null;
            } else {
                let eventList = event.split(",");
                if (eventList.length > 1) eventList.forEach((listen => listenerElement.addEventListener(listen, listenerFn, option))); else listenerElement.addEventListener(event, listenerFn, option);
            }
        };
        if ($type(element) === "Array") return element.forEach(((ele, i) => addListener(ele, i)));
        addListener(element);
    } catch (e) {
        $omjsError("$on", e, true);
    }
};

const $set = listener => $on($doc, "DOMContentLoaded", listener);

const $load = listener => $on($win, "load", listener);

const $attr = (element, attributeName, attributeValue = null) => {
    try {
        if (attributeValue) {
            if (attributeValue === "remove" || attributeValue === "del") return element.removeAttribute(attributeName); else return element.setAttribute(attributeName, attributeValue);
        }
        return element.getAttribute(attributeName);
    } catch (e) {
        $omjsError("$attr", e, true);
    }
};

const $data = (element, dataName, value) => {
    if (value) return $attr(element, "data-" + dataName, value);
    return $attr(element, "data-" + dataName);
};

const $class = (element, action = null, ...className) => {
    if (!action) return element.classList; else if (action === "contains" || action === "contain" || action === "has") return element.classList.contains(className); else if (action === "index" || action === "key") {
        if (element) {
            let rtn = 0;
            $sela("." + element.classList.toString().replace(" ", ".")).forEach(((v, i) => {
                if (v === element) return rtn = i;
            }));
            return rtn;
        }
        return 0;
    }
    return className.forEach((classValue => {
        if (action === "add") {
            element.classList.add(classValue);
            return element;
        } else if (action === "remove" || action === "del") {
            element.classList.remove(classValue);
            return element;
        } else {
            element.classList.toggle(classValue);
            return element;
        }
    }));
};

const $style = (element, cssProperties = null, pseudoElement = null) => {
    try {
        if (cssProperties === "css") return $win.getComputedStyle(element, pseudoElement);
        if (cssProperties !== null) return $attr(element, "style", cssProperties);
        return element.style;
    } catch (e) {
        $omjsError("$style", e, true, "%cThe selected element doesn't exist", "color:#e0a800");
    }
};

const $html = (element, where = null, code__moveTo = null) => {
    if (where === "inner" || where === "in") {
        try {
            return element.innerHTML = code__moveTo;
        } catch (e) {
            $omjsError("$html", e, true, "%cThe selected element doesn't exist", "color:#e0a800");
        }
    } else if (where === "del" || where === "remove") {
        try {
            return element.innerHTML = "";
        } catch (e) {
            $omjsError("$html", e, true, "%cThe selected element doesn't exist", "color:#e0a800");
        }
    } else if (where === "move") {
        try {
            return code__moveTo.appendChild(element);
        } catch (e) {
            $omjsError("$html", e, true, "%cEnsure `param 1` === Element being moved to `param 3`\nEnsure `param 3` === Parent Element receiving `param 1`", "color:#e0a800");
        }
    } else if (where === "wrap") {
        try {
            element.parentNode.insertBefore($doc.createElement(code__moveTo), element);
            return element.previousElementSibling.appendChild(element).parentElement;
        } catch (e) {
            $omjsError("$html", e, true, "%cEnsure the first parameter is a valid node\nEnsure a valid tag name was supplied to the third parameter` === Parent Element receiving `param 1`", "color: #e0a800");
        }
    } else if (!code__moveTo && !where) return element.innerHTML; else return element.insertAdjacentHTML(where, code__moveTo);
};

const $type = (element, silent = true) => {
    let result = $obj.prototype.toString.call(element).replace("[object ", "").replace("]", "");
    if (silent === false) {
        console.log("%cOMJ$ VIEW: $type", "background: #fff3cd; color: #1d2124; padding: 5px");
        console.info(element);
        console.log("%cObject Type: " + result, "background: #14242f; color: #fffffa; padding: 5px;");
    }
    return result;
};

const $loop = (obj, operation = (() => null)) => {
    let prop = {
        length: 0,
        first: {
            key: "",
            value: ""
        },
        last: {
            key: "",
            value: ""
        }
    };
    if (!isNaN(obj)) {
        if ($type(obj) === "Array" && obj.length === 0) return prop;
        if ($type(operation) === "Function") operation = {};
        let i = obj;
        let run = 10;
        let cond = operation.while ?? null;
        let fun = operation.then ?? (i => console.log("Index:", i));
        let infinity = operation.infinite ?? false;
        let by = operation.by ?? 1;
        if (!cond) cond = i => prop.length < run;
        while (cond(i)) {
            prop.length++;
            prop.last.key = i;
            if (prop.length === 1) prop.first.key = i;
            let x = fun(i);
            if (x === "continue") continue;
            if (x === "break") break;
            if (infinity === false && prop.length > 999) $omjsError("$loop", "Infinite loop detected, process was ended prematurely to save resources. Please pass `infinite: true` if you intend for the loop to go beyond '1000' iterations", true);
            i += by;
        }
        return prop;
    }
    let fun = operation ?? ((k, v) => console.log("keys:", k, "\nvalue:", v));
    let previousOutput = "";
    for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
            prop.length++;
            key = $isInt(key);
            prop.last.key = key;
            prop.last.value = obj[key];
            if (prop.length === 1) {
                prop.first.key = key;
                prop.first.value = obj[key];
            }
            let x = fun(obj[key], key, previousOutput);
            if (x === "continue") continue;
            if (x === "break") break;
            prop.output = x ?? null;
            previousOutput = prop.output;
            prop.outputType = $type(prop.output);
        }
    }
    return prop;
};

/**!
 * @fileOverview Helpful Plugins Developed With OMJ$
 * @author Osahenrumwen Aigbogun
 * @version 2.0.3
 * @since 23/11/2019
 * @modified 09/01/2022
 * @license Copyright (c) 2019 Osai LLC | loshq.net/about.
 */
const $in = (element, parent__selector = $doc, mode = "down") => {
    if (mode === "parent" || mode === "top") {
        if (parent === $doc) return false;
        try {
            if ($type(parent__selector) === "String") return element.closest(parent__selector);
            let x = element.parentNode;
            while (x) {
                if (x === $sel("body")) break; else if (x === parent__selector) return x;
                x = x.parentElement;
            }
        } catch (e) {
            $omjsError("$in", e, true);
        }
        return false;
    } else return parent__selector.contains(element);
};

const $get = (name, query = true) => {
    if (query) return new URLSearchParams($loc.search).get(name);
    let origin = $loc.origin;
    let path = $loc.pathname;
    let urlFileName = $end(path.split("/"));
    let hash = $loc.hash ? $loc.hash.split("#")[1] : "";
    let urlComplete = origin + path;
    path = path.replace("/" + urlFileName, "");
    switch (name) {
      case "origin":
        return origin;

      case "path":
      case "directory":
        return path;

      case "file":
      case "script":
        return urlFileName;

      case "hash":
        return hash;

      default:
        return urlComplete;
    }
};

const $ucFirst = string => {
    let fullString = "";
    string.split(" ").forEach((word => {
        let smallLetter = word.charAt(0);
        fullString += " " + word.replace(smallLetter, smallLetter.toUpperCase());
    }));
    return fullString.trim();
};

const $mirror = (parentField, ...children) => {
    $on(parentField, "input", (() => {
        children.forEach((kid => {
            if (parentField.value === undefined) kid.value = parentField.innerHTML; else kid.value = parentField.value;
        }));
    }));
};

const $mediaPreview = (elementToWatch, placeToPreview, other = {}) => {
    let placeholder = other.default ?? null;
    let type = other.type ?? 0;
    let event_wrap = other.event ?? true;
    let operation = other.operation ?? (() => "operation");
    let previewPlaceholder = placeholder ?? placeToPreview.src;
    let previewMedia = () => {
        let srcProcessed = [];
        if (type === 1) {
            let reader = new FileReader;
            $on(reader, "load", (() => {
                if (elementToWatch.value !== "") {
                    placeToPreview.src = reader.result;
                    if (operation !== "operation") operation(reader.result);
                } else placeToPreview.src = previewPlaceholder;
            }), "on");
            reader.readAsDataURL(elementToWatch.files[0]);
        } else if (type === 2) placeToPreview.src = elementToWatch.value !== "" ? elementToWatch.value : previewPlaceholder; else {
            if (placeToPreview !== "multiple") {
                if (elementToWatch.value !== "") {
                    srcProcessed = URL.createObjectURL(elementToWatch.files[0]);
                    placeToPreview.src = srcProcessed;
                } else placeToPreview.src = previewPlaceholder;
            } else {
                if (elementToWatch.value !== "") Array.from(elementToWatch.files).forEach((file => srcProcessed.push(URL.createObjectURL(file))));
            }
            if (operation !== "operation") operation(srcProcessed);
        }
    };
    if (event_wrap === true) $on(elementToWatch, "change", previewMedia, "on"); else if ($type(event_wrap) === "String") $on(elementToWatch, event_wrap, previewMedia, "on"); else previewMedia();
};

const $showPassword = () => {
    let selector = $id("toggle-password") ? "#toggle-password" : ".osai-show-password";
    $sela(selector).forEach((ele => {
        $on(ele, "click", (() => {
            let fields = $data(ele, "field");
            if (!fields) return;
            fields.split(",").forEach((field => {
                let target = $sel(field);
                if (target) target.type = target.type === "password" ? "text" : "password";
            }));
        }), "on");
    }));
};

const $rand = (min, max, mode = 0, silent = true) => {
    min = Math.ceil(min), max = Math.floor(max);
    let x;
    if (mode === 1) {
        x = Math.floor(Math.random() * (max - min)) + min;
        if (silent === false) console.log("Rand (x => r < y):", x);
        return x;
    }
    x = Math.floor(Math.random() * (max - min + 1)) + min;
    if (silent === false) console.log("Rand (x => r <= y):", x);
    return x;
};

const $view = element => {
    let rect = element.getBoundingClientRect();
    let top = rect.top;
    let left = rect.left;
    let right = rect.right;
    let bottom = rect.bottom;
    let viewHeight = $win.innerHeight;
    let viewWidth = $win.innerWidth;
    let inView = true;
    if (top < 0 && bottom < 0 || top > viewHeight && bottom > viewHeight || (left < 0 && right < 0 || left > viewWidth && right > viewWidth)) inView = false;
    return {
        top: top,
        left: left,
        bottom: bottom,
        right: right,
        inView: inView
    };
};

const $hasFocus = element => {
    let active = $doc.activeElement;
    if ($in(active, element, "top")) return true;
    return active === element;
};

const $overflow = element => element.scrollHeight > element.clientHeight || element.scrollWidth > element.clientWidth;

const $check = (value, type) => {
    if ($type(value) !== "String") return false;
    switch (type) {
      case "name":
        return !!new RegExp("^[a-z ,.'-]+/i$", value);

      case "username":
        return !!new RegExp("^w+$", value);

      case "mail":
        return /^([a-zA-Z0-9_.\-+])+@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(value);

      default:
        return true;
    }
};

const $cookie = (name = "*", value = null, expire = null, path = "", domain = "") => {
    if (name === "*") return $doc.cookie.split(";");
    if (value === "del") return $doc.cookie = name + "=" + value + "; expires=Thu, 01 Jan 1970 00:00:00 UTC";
    if (value) {
        const d = new Date, dn = new Date(d), days = (duration = 30) => dn.setDate(d.getDate() + duration);
        if ($type(expire) === "Number") expire = days(expire);
        expire = expire ?? new Date(days()).toUTCString();
        if (path) path = "path=" + path + ";";
        if (domain) domain = "domain=" + domain + ";";
        return $doc.cookie = `${name}=${value};expires=${expire};${path}${domain}"`;
    }
    let nameString = name + "=";
    value = $doc.cookie.split(";").filter((item => item.includes(nameString)));
    if (value.length) {
        value[0] = value[0].trim();
        return value[0].substring(nameString.length, value[0].length);
    }
    return "";
};

/**!
 * This Function validates form, it doesn't get the form data (serialize), that's done by $getForm
 * @param element {HTMLElement|HTMLFormElement} Element to trigger form check or the form element itself
 * @param option {Object} Element to trigger form check or the form element itself
 * {string|function} option.errorDisplay manner in which the error message should be displayed [default=popUp]
 *      @value {errorDisplay} === "popUp"[default] || "create"[create error message after field] || function(){}
 * {string} option.errorMessage error text to return to user (not necessary if using function for {errorDisplay})
 * @return {boolean} [false] if field(s) is|are empty || [true] if field(s) is|are not empty
 */ const $form = (element, option = {}) => {
    let errorDisplay = option.display ?? "popUp";
    let errorMessage = option.message ?? "Please fill all required fields!";
    if (!(element.nodeName === "FORM")) element = element.closest("FORM");
    let elem = element.elements;
    let xErrMsg = () => {
        let e = $id("osai-err-msg");
        return $in(e) && e.remove();
    };
    let xTest = () => {
        $sela("input[data-osai-tested='true']").forEach((test => {
            $data(test, "osai-tested", "del");
        }));
    };
    let aErrMsg = (formField, customMsg = errorMessage) => {
        if (errorDisplay === "popUp") {
            if ($data(formField, "osai-error") === null || $data(formField, "osai-error") === "") $data(formField, "osai-error", $style(formField, "css").background);
            osNote(customMsg, "danger");
            setTimeout((() => {
                formField.style.background = "#f40204 none padding-box";
                formField.focus();
            }), 100);
            $on(formField, "input,change", (() => formField.style.background = $data(formField, "osai-error")), "addEvent");
        } else if (errorDisplay === "create") {
            let errBx = $id("osai-err-msg");
            $in(errBx) && errBx.remove();
            $html(formField, "afterend", `<div id="osai-err-msg">${customMsg}</div>`);
            setTimeout((() => {
                $style($id("osai-err-msg"), "font-size: 14px; background-color: #e25656; color: #fff; padding: 5px; margin: 5px auto; border-radius: 4px"), 
                formField.focus();
            }), 700);
            $on(formField, "input", xErrMsg, "addEvent");
        } else {
            try {
                errorDisplay();
            } catch (e) {
                $omjsError("$form", e, true, `%c "display" param can only take the following;\n"popup" for a popup notification\n"create" for a message directly under the required field\nOR a custom "function" from dev`, "background: #fff3cd; color: #1d2124");
            }
        }
        xTest();
        return false;
    };
    for (let i = 0; i < elem.length; i++) {
        let field = elem[i], test = field.name && field.required && field.disabled === false;
        if (test && (field.value.trim() === "" || field.value === undefined || field.value === null)) return aErrMsg(field); else if (test && field.type === "email" && !$check(field.value, "mail")) return aErrMsg(field, "Invalid email format, should be <div style='font-weight: bold; text-align: center'>\"[A-Za-Z_.-]@[A-Za-Z.-].[A-Za-Z_.-].[A-Za-Z]\"</div>"); else if (test && (field.type === "radio" || field.type === "checkbox") && !$data(field, "osai-tested")) {
            let marked = 0;
            $name(field.name).forEach((radio => {
                $data(radio, "osai-tested", "true");
                if (marked === 1) return;
                if (radio.checked) marked = 1;
            }));
            if (marked === 0) return aErrMsg(field, "Please select the required number of options from the required checklist");
        }
    }
    xTest();
    return true;
};

/**!
 * Acquire form data as string, object or FormData
 * @param {HTMLFormElement|HTMLElement}  form = Form to be fetched or an existing element within the form
 * @param {boolean} validate = if to validate form automatically [default = false]
 * @return {Object}
 * @example $getForm(formElement).string || $getForm(formElement).object || $getForm(formElement).file
 */ const $getForm = (form, validate = false) => {
    let formFieldsString = "";
    let formFieldsObject = {};
    let hasFile = false;
    let findForm = () => {
        if (form) {
            if (form.nodeName === "FORM") return form;
            return form.closest("FORM");
        }
    };
    let addField = (fieldName, value) => {
        formFieldsString += encodeURIComponent(fieldName) + "=" + encodeURIComponent(value) + "&";
        if ($end(fieldName) === "]") {
            let name = fieldName.replace("[]", "");
            if (!formFieldsObject[name]) formFieldsObject[name] = [];
            formFieldsObject[name].push(value);
        } else formFieldsObject[fieldName] = value;
    };
    if (validate && !$form(findForm())) throw Error("Your form has not satisfied all required validation!");
    form = findForm();
    let alreadyChecked;
    for (let i = 0; i < form.elements.length; i++) {
        let field = form.elements[i];
        if (field.name && field.type === "file" && field.disabled === false) hasFile = true;
        if (!field.name || field.disabled || field.type === "file" || field.type === "reset" || field.type === "submit" || field.type === "button") continue;
        if (field.type === "select-multiple") $loop(field.options, (v => {
            if (v.selected) addField(field.name, v.value);
        })); else if (field.type === "checkbox" || field.type === "radio") {
            let all = $name(field.name);
            if (alreadyChecked === all) continue;
            alreadyChecked = all;
            if (all.length < 2) field.checked ? addField(field.name, field.value !== "" ? field.value : field.checked) : addField(field.name, field.checked); else $loop(all, (check => {
                if (field.type === "radio" && check.checked) {
                    addField(field.name, check.value !== "" ? check.value : check.checked);
                    return "break";
                } else if (field.type === "checkbox" && check.checked) {
                    addField(field.name, check.value !== "" ? check.value : check.checked);
                }
            }));
        } else addField(field.name, field.value);
    }
    return {
        string: formFieldsString.slice(0, -1),
        object: formFieldsObject,
        file: new FormData(findForm()),
        hasFile: hasFile
    };
};

const $drag = (element, elementAnchor) => {
    let pos1 = 0;
    let pos2 = 0;
    let pos3 = 0;
    let pos4 = 0;
    if (elementAnchor) elementAnchor.onmousedown = dragMouseDown; else element.onmousedown = dragMouseDown;
    function dragMouseDown(e) {
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        $on($doc, "mouseup", (() => {
            $doc.onmouseup = null;
            $doc.onmousemove = null;
        }));
        $on($doc, "mousemove", (e => {
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            element.style.top = element.offsetTop - pos2 + "px";
            element.style.left = element.offsetLeft - pos1 + "px";
        }));
    }
};

const $preloader = (act = "show") => {
    if (!$sel(".osai-preloader")) $html($sel("body"), "beforeend", `<div class="osai-preloader" style="display:none"><div class="osai-preloader__container"><svg version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300.000000 300.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,300.000000) scale(0.100000,-0.100000)" fill="#041038" stroke="none"><path d="M115 2978 c-16 -6 -38 -18 -49 -27 -47 -41 -46 -32 -46 -701 l0 -630 25 0 c18 0 25 -5 25 -20 0 -15 -7 -20 -25 -20 -21 0 -25 -5 -25 -30 0 -25 4 -30 25 -30 18 0 25 -5 25 -20 0 -15 -7 -20 -25 -20 -21 0 -25 -5 -25 -30 0 -25 4 -30 25 -30 18 0 25 -5 25 -20 0 -15 -7 -20 -25 -20 l-25 0 0 -634 0 -633 23 -34 c47 -72 21 -69 715 -69 l622 0 0 28 c0 21 5 28 20 29 16 1 20 -4 20 -28 0 -26 3 -29 30 -29 27 0 30 3 30 30 0 23 4 30 20 30 16 0 20 -7 20 -30 0 -27 3 -30 30 -30 27 0 30 3 30 30 0 23 4 30 20 30 16 0 20 -7 20 -30 l0 -30 623 0 c693 0 667 -3 714 69 l23 34 0 633 0 634 -25 0 c-18 0 -25 5 -25 20 0 15 7 20 25 20 21 0 25 5 25 30 0 25 -4 30 -25 30 -18 0 -25 5 -25 20 0 15 7 20 25 20 21 0 25 5 25 30 0 25 -4 30 -25 30 -18 0 -25 5 -25 20 0 15 7 20 25 20 l25 0 0 630 c0 695 3 665 -62 712 l-33 23 -632 3 -633 3 0 -31 c0 -23 -4 -30 -20 -30 -16 0 -20 7 -20 30 0 27 -3 30 -30 30 -27 0 -30 -3 -30 -30 0 -23 -4 -30 -20 -30 -16 0 -20 7 -20 30 0 27 -3 30 -30 30 -27 0 -30 -3 -30 -30 0 -23 -4 -30 -20 -30 -16 0 -20 7 -20 30 l0 30 -617 -1 c-405 0 -628 -4 -648 -11z m118 -15 c-13 -2 -35 -2 -50 0 -16 2 -5 4 22 4 28 0 40 -2 28 -4z m54 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m110 0 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m61 -1 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m85 0 c-7 -2 -21 -2 -30 0 -10 3 -4 5 12 5 17 0 24 -2 18 -5z m50 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m90 0 c-13 -2 -35 -2 -50 0 -16 2 -5 4 22 4 28 0 40 -2 28 -4z m85 0 c-10 -2 -28 -2 -40 0 -13 2 -5 4 17 4 22 1 32 -1 23 -4z m49 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m61 -1 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m55 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m65 0 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m49 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m90 0 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m61 -1 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m129 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m391 -1 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m189 0 c-20 -2 -52 -2 -70 0 -17 2 0 4 38 4 39 0 53 -2 32 -4z m90 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m50 0 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m61 -1 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m85 0 c-7 -2 -21 -2 -30 0 -10 3 -4 5 12 5 17 0 24 -2 18 -5z m50 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m90 0 c-13 -2 -35 -2 -50 0 -16 2 -5 4 22 4 28 0 40 -2 28 -4z m85 0 c-10 -2 -28 -2 -40 0 -13 2 -5 4 17 4 22 1 32 -1 23 -4z m49 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m61 -1 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m55 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m65 0 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m49 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m141 -1 c-16 -2 -40 -2 -55 0 -16 2 -3 4 27 4 30 0 43 -2 28 -4z m-2752 -58 c-16 -16 -36 -20 -36 -7 0 14 12 22 32 22 17 0 18 -2 4 -15z m2809 -9 l24 -25 0 -1371 0 -1371 -24 -25 -24 -24 -1369 0 c-1290 0 -1371 1 -1391 18 l-21 17 0 1385 0 1385 21 18 c20 16 101 17 1391 17 l1369 0 24 -24z m40 15 c-3 -5 3 -12 12 -14 14 -4 14 -5 -3 -6 -12 0 -24 6 -28 14 -3 9 0 15 10 15 8 0 13 -4 9 -9z m-2871 -76 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2896 -70 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2896 -70 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2896 -70 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2896 -70 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2896 -70 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2900 -69 c0 -8 -4 -17 -9 -21 -12 -7 -24 12 -16 25 9 15 25 12 25 -4z m2900 -1 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2901 -76 c-7 -14 -14 -18 -21 -11 -13 13 -2 32 18 32 12 0 13 -4 3 -21z m2901 6 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2905 -85 c-6 -9 -9 -9 -16 1 -10 17 0 34 13 21 6 -6 7 -16 3 -22z m2905 9 c0 -11 -4 -17 -10 -14 -5 3 -10 13 -10 21 0 8 5 14 10 14 6 0 10 -9 10 -21z m-2900 -63 c0 -8 -4 -17 -9 -21 -12 -7 -24 12 -16 25 9 15 25 12 25 -4z m2900 -1 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2905 -85 c-6 -9 -9 -9 -16 1 -10 17 0 34 13 21 6 -6 7 -16 3 -22z m2905 9 c0 -11 -4 -17 -10 -14 -5 3 -10 13 -10 21 0 8 5 14 10 14 6 0 10 -9 10 -21z m-2905 -79 c-5 -8 -11 -8 -17 -2 -6 6 -7 16 -3 22 5 8 11 8 17 2 6 -6 7 -16 3 -22z m2905 15 c0 -8 -4 -15 -10 -15 -5 0 -10 7 -10 15 0 8 5 15 10 15 6 0 10 -7 10 -15z m-2909 -87 c-11 -10 -13 -10 -7 0 4 6 2 12 -4 12 -6 0 -8 5 -4 11 4 8 9 8 17 0 8 -8 7 -14 -2 -23z m2909 11 c0 -11 -4 -17 -10 -14 -5 3 -10 13 -10 21 0 8 5 14 10 14 6 0 10 -9 10 -21z m-2900 -63 c0 -8 -4 -18 -10 -21 -6 -4 -7 1 -3 11 5 13 3 15 -7 9 -9 -5 -11 -4 -6 3 10 17 26 15 26 -2z m2900 -7 c0 -11 -4 -17 -10 -14 -5 3 -10 13 -10 21 0 8 5 14 10 14 6 0 10 -9 10 -21z m-2900 -75 c0 -8 -5 -14 -11 -14 -5 0 -7 5 -4 10 3 6 1 10 -5 10 -6 0 -8 5 -5 10 8 13 25 3 25 -16z m2900 4 c0 -16 -3 -19 -11 -11 -6 6 -8 16 -5 22 11 17 16 13 16 -11z m-2909 -80 c-11 -10 -13 -10 -7 0 4 6 2 12 -4 12 -6 0 -8 5 -4 11 4 8 9 8 17 0 8 -8 7 -14 -2 -23z m2909 10 c0 -19 -2 -20 -10 -8 -13 19 -13 30 0 30 6 0 10 -10 10 -22z m0 -70 c0 -18 -2 -20 -9 -8 -6 8 -7 18 -5 22 9 14 14 9 14 -14z m-2900 -3 c0 -8 -5 -15 -11 -15 -5 0 -8 4 -5 9 4 5 0 12 -6 14 -8 3 -6 6 5 6 9 1 17 -6 17 -14z m0 -420 c0 -8 -5 -15 -11 -15 -5 0 -8 4 -5 9 4 5 0 12 -6 14 -8 3 -6 6 5 6 9 1 17 -6 17 -14z m2900 0 c0 -8 -2 -15 -4 -15 -2 0 -6 7 -10 15 -3 8 -1 15 4 15 6 0 10 -7 10 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -2 -15 -4 -15 -2 0 -6 7 -10 15 -3 8 -1 15 4 15 6 0 10 -7 10 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -2 -15 -4 -15 -2 0 -6 7 -10 15 -3 8 -1 15 4 15 6 0 10 -7 10 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -2 -15 -4 -15 -2 0 -6 7 -10 15 -3 8 -1 15 4 15 6 0 10 -7 10 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0-8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -7 -15 -14 -15 -8 0 -12 4 -9 9 4 5 0 12 -6 14 -7 3 -4 6 8 6 12 1 21 -5 21 -14z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -9 -9 -15 -21 -14 -13 0 -16 3 -7 6 11 5 11 7 0 14 -11 7 -9 9 7 9 12 0 21 -6 21 -15z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -9 -9 -15 -21 -14 -13 0 -16 3 -7 6 11 5 11 7 0 14 -11 7 -9 9 7 9 12 0 21 -6 21 -15z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -9 -15 -19 -15 -18 0 -24 11 -11 23 12 12 30 7 30 -8z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -9 -9 -15 -21 -14 -13 0 -16 3 -7 6 11 5 11 7 0 14 -11 7 -9 9 7 9 12 0 21 -6 21 -15z m2893 0 c0 -8 -5 -15 -11 -15 -5 0 -8 4 -5 9 4 5 0 12 -6 14 -8 3 -6 6 5 6 9 1 17 -6 17 -14z m-2893 -70 c0 -8 -9 -15 -19 -15 -18 0 -24 11 -11 23 12 12 30 7 30 -8z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2893 -70 c0 -8 -9 -15 -19 -15 -18 0 -24 11 -11 23 12 12 30 7 30 -8z m2893 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2896 -70 c-6 -16 -34 -21 -34 -7 0 13 11 22 26 22 8 0 11 -6 8 -15z m2896 0 c0 -8 -4 -15 -8 -15 -5 0 -9 7 -9 15 0 8 4 15 9 15 4 0 8 -7 8 -15z m-2876 -71 c4 -11 1 -14 -11 -12 -9 2 -18 9 -21 16 -6 18 25 15 32 -4z m2864 9 c-10 -2 -18 -9 -18 -14 0 -5 -4 -9 -10 -9 -5 0 -7 7 -4 15 4 8 16 14 28 14 16 -1 17 -2 4 -6z m-2770 -70 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m100 0 c-10 -2 -28 -2 -40 0 -13 2 -5 4 17 4 22 1 32 -1 23 -4z m140 0 c-15 -2 -42 -2 -60 0 -18 2 -6 4 27 4 33 0 48 -2 33 -4z m65 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m120 0 c-13 -2 -35 -2 -50 0 -16 2 -5 4 22 4 28 0 40 -2 28 -4z m90 0 c-7 -2 -21 -2 -30 0 -10 3 -4 5 12 5 17 0 24 -2 18 -5z m54 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m40 0 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m86 -1 c-13 -2 -35 -2 -50 0 -16 2 -5 4 22 4 28 0 40 -2 28 -4z m90 0 c-13 -2 -33 -2 -45 0 -13 2 -3 4 22 4 25 0 35 -2 23 -4z m85 0 c-10 -2 -28 -2 -40 0 -13 2 -5 4 17 4 22 1 32 -1 23 -4z m125 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m60 0 c-7 -2 -21 -2 -30 0 -10 3 -4 5 12 5 17 0 24 -2 18 -5z m70 0 c-7 -2 -21 -2 -30 0 -10 3 -4 5 12 5 17 0 24 -2 18 -5z m44 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m391 -1 c-15 -2 -42 -2 -60 0 -18 2 -6 4 27 4 33 0 48 -2 33 -4z m90 0 c-10 -2 -26 -2 -35 0 -10 3 -2 5 17 5 19 0 27 -2 18 -5z m100 0 c-10 -2 -28 -2 -40 0 -13 2 -5 4 17 4 22 1 32 -1 23 -4z m140 0 c-15 -2 -42 -2 -60 0 -18 2 -6 4 27 4 33 0 48 -2 33 -4z m65 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m120 0 c-13 -2 -35 -2 -50 0 -16 2 -5 4 22 4 28 0 40 -2 28 -4z m144 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z m110 -1 c-20 -2 -52 -2 -70 0 -17 2 0 4 38 4 39 0 53 -2 32 -4z m106 0 c-13 -2 -33 -2 -45 0 -13 2 -3 4 22 4 25 0 35 -2 23 -4z m85 0 c-10 -2 -28 -2 -40 0 -13 2 -5 4 17 4 22 1 32 -1 23 -4z m75 0 c-7 -2 -21 -2 -30 0 -10 3 -4 5 12 5 17 0 24 -2 18 -5z m50 0 c-7 -2 -19 -2 -25 0 -7 3 -2 5 12 5 14 0 19 -2 13 -5z m44 1 c-3 -3 -12 -4 -19 -1 -8 3 -5 6 6 6 11 1 17 -2 13 -5z"/><path d="M320 2090 l0 -590 210 0 210 0 0 380 0 380 205 0 204 0 3 210 3 210 -417 0 -418 0 0 -590z m805 398 c-1 -90 -3 -171 -3 -180 -2 -16 -18 -18 -193 -18 -154 0 -193 -3 -205 -15 -12 -12 -15 -70 -14 -367 0 -194 -4 -357 -8 -363 -13 -17 -8 -17 -264 -16 l-88 1 0 560 0 560 388 0 388 0 -1 -162z"/><path d="M1848 2470 l3 -210 414 0 415 0 0 210 0 210 -417 0 -418 0 3 -210z m800 -2 l2 -178 -388 0 -388 0 -3 180 -2 180 388 -2 388 -3 3 -177z"/><path d="M2310 1755 l-50 -51 0 -102 0 -102 133 0 132 0 78 78 77 77 0 75 0 75 -160 0 -161 0 -49 -50z m338 -35 l3 -56 -75 -73 -75 -72 -103 3 -103 3 -3 83 c-3 81 -2 85 28 118 16 19 30 32 30 29 0 -4 7 1 16 10 13 13 38 15 147 13 l132 -3 3 -55z"/><path d="M1210 1645 l0 -145 290 0 290 0 0 145 0 145 -290 0 -290 0 0 -145z m508 115 l42 0 0 -114 0 -113 -152 -6 c-84 -3 -184 -3 -223 -2 -38 2 -87 4 -107 4 l-38 1 0 113 c0 63 3 117 6 120 4 3 102 4 218 1 116 -2 230 -4 254 -4z"/><path d="M2260 700 l0 -380 210 0 210 0 0 380 0 380 -210 0 -210 0 0 -380z m390 0 l0 -350 -180 0 -180 0 0 350 0 350 180 0 180 0 0 -350z"/><path d="M320 530 l0 -210 210 0 210 0 0 210 0 210 -210 0 -210 0 0 -210z m379 166 c8 -9 11 -68 10 -180 l-1 -166 -179 0 -179 0 0 180 0 180 169 0 c131 0 171 -3 180 -14z"/><path d="M1210 530 l0 -210 290 0 290 0 0 210 0 210 -290 0 -290 0 0 -210z m550 0 l0 -180 -260 0 -259 0 0 180 -1 180 260 0 260 0 0 -180z"/></g></svg><span>Loading...please wait</span></div></div>`);
    if (!$sel(".osai-preloader-css")) $html($sel("head"), "beforeend", `<style type="text/css" class="osai-preloader-css">.osai-preloader{display: flex;position: fixed;width: 101vw;height: 101vh;justify-content: center;align-items: center;background: rgba(8,11,31,0.8);left: -5px;right: -5px;top: -5px;bottom: -5px;z-index:9993}.osai-preloader__container{display: table; text-align: center;margin:0;padding:0;}.osai-preloader svg{width: 80px;background: #f2f1fe;padding: 1px;border-radius: 5px;animation: pulse 2s infinite linear;transition: .6s ease-in-out}.osai-preloader span{color: #fff;text-align: center;margin-top: 10px;display: block}@keyframes pulse {0% {transform: scale(0.6);opacity: 0}33% {transform: scale(1);opacity: 1}100%{transform: scale(1.4);opacity: 0}}</style>`);
    if (act === "show") return $style($sel(".osai-preloader"), "del");
    return $style($sel(".osai-preloader"), "display:none");
};

"use strict";

/**!
 * CURL (AJAX) built with OMJ$
 * @author Osahenrumwen Aigbogun
 * @version 2.0.1
 * @copyright (c) 2019 Osai LLC | loshq.net/about.
 * @since 05/01/2021
 * @modified 25/12/2021
 * @param url {string|Object} = url of request being sent or an object containing the url and options of the request
 * url should be passed using "action" as the key
 * @param option {Object}
 *  `option.credential` {boolean} = send request with credentials when working with CORS
 *  `option.content` {string} = XMLHTTPRequest [default = text/plain] only necessary when user wants to set custom dataType aside json,xml and native formData
 *  `option.method` {string} = method of request [default = GET]
 *  `option.data` {any} [use data or form] = data sending [only necessary for post method]. It could be HTMLElement inside the form, like button, etc
 *  `option.type` {string} = type of data to be sent/returned [default = text]
 *  `option.alert` {bool} = to use js default alert or OMJ$ default alert notifier [default=false]
 *  `option.strict` {bool} = [default=false] when true, automatic JSON.parse for resolve that comes as JSON text will be stopped
 *  `option.preload` {function} = function to carryout before response is received
 *  `option.progress` {function} = function to execute, while upload is in progress [one arg (response)]
 *  `option.error` {function} = it executes for all kinds of error, it's like the finally of errors
 *  `option.loaded` {function} = optional callback function that should be executed when the request is successful, either this or a promise
 *  `option.abort` {function} = function to execute on upload abort
 * @param data {any} same as `option.data`, only comes in play when three parameter wants to be used
 * @return {Promise}
 */ const $curl = (url, option = {}, data = null) => new Promise(((resolve, reject) => {
    if ($type(url) === "Object") {
        option = url;
        url = option.action;
    }
    if ($type(option) === "Function") option = {
        preload: option,
        type: "json"
    };
    if ($type(option) === "String") option = {
        type: option
    };
    if ($type(data) === "Boolean") {
        option.strict = data;
        data = null;
    }
    let xhr = false, response;
    let credential = option.credential ?? false;
    let content = option.content ?? "text/plain";
    let method = option.method ?? "get";
    data = option.data ?? option.form ?? data ?? null;
    let type = option.type ?? "text";
    let returnType = option.return ?? option.type ?? null;
    let alert_error = option.alert ?? false;
    let strict = option.strict ?? true;
    let preload = option.preload ?? (() => "preload");
    let progress = option.progress ?? (() => "progress");
    let error = option.error ?? (() => "error");
    option.timeout = option.timeout ?? {
        value: 0
    };
    let timeout = {
        value: option.timeout.value ?? option.timeout,
        then: (e, xhr) => {
            clearTimeout(connectionTimer);
            errRoutine("Request timed out, please try again later!", xhr);
            if (option.timeout.then) option.timeout.then(e);
        }
    };
    let loaded = option.loaded ?? (() => "loaded");
    let abort = option.abort ?? (() => osNote("Request aborted!", "warn"));
    let errRoutine = (msg, xhr) => {
        if (error(xhr.status, xhr) === "error") {
            if (strict) {
                if(alert_error)
                    alert(msg)
                else
                    osNote(msg, "fail", {
                        duration: -1
                    });
            }
            $omjsError("$curl", xhr.e ?? xhr.statusText);
            reject(Error(xhr.e ?? xhr.statusText), xhr);
        }
    };
    method = method.toUpperCase();
    type = type.toLowerCase();
    xhr = new XMLHttpRequest;
    if (!xhr) return;
    method = data ? "post" : method;
    xhr.withCredentials = credential;
    xhr.timeout = timeout.value;
    let timer = 0;
    let connectionTimer = setInterval((() => timer++), 1e3);
    xhr.open(method, url, true);
    if (data && ($type(data) !== "String" && $type(data) !== "Object" && $type(data) !== "FormData")) {
        data = $getForm(data, true);
        if (data.hasFile) {
            data = data.file;
            type = "file";
        } else data = type === "json" ? data.object : data.string;
    }
    if (option.xhrSetup) option.xhrSetup(xhr);
    if (type !== "file") {
        let requestHeader = "application/x-www-form-urlencoded";
        if (type === "json") {
            requestHeader = method === "get" ? requestHeader : "application/json";
            data = JSON.stringify(data);
        } else if (type === "text" && $type(data) === "Object") {
            data = $loop(data, ((value, name) => {
                value = name + "=" + value + "&";
                return value;
            }), (value => value.replace(/&+$/, ""))).output;
        } else if (type === "xml" && method !== "GET") requestHeader = "text/xml"; else if (type === "custom" && method !== "GET") requestHeader = content;
        xhr.setRequestHeader("Content-Type", requestHeader);
    }
    $on(xhr.upload, "progress", (event => progress(event)));
    $on(xhr, "error", (() => errRoutine("An error occurred" + xhr.statusText, xhr)));
    $on(xhr, "abort", abort);
    $on(xhr, "timeout", (e => timeout.then(e, xhr)), "on");
    $on(xhr, "readystatechange", (event => {
        let status = xhr.status;
        if (xhr.readyState === 4) {
            type = returnType ?? "json";
            switch (status) {
              case 0:
                if (timer !== xhr.timeout / 1e3) errRoutine(`Failed, ensure you have steady connection and try again, server request might be too heavy for your current network`, xhr);
                break;

              case 200:
                response = method === "HEAD" ? xhr : xhr.responseText;
                if (method !== "HEAD") {
                    if (type !== "json" && (response.trim().substring(0, 1) === "{" || response.trim().substring(0, 1) === "[")) type = "json";
                    if (type === "xml") response = xhr.responseXML;
                    if (type === "json") {
                        try {
                            response = JSON.parse(xhr.response);
                        } catch (e) {
                            xhr["e"] = e;
                            errRoutine("Server-side error, please contact support if problem persists", xhr);
                        }
                    }
                }
                if (loaded !== "loaded") loaded(response, xhr, event);
                resolve(response, xhr, event);
                break;

              default:
                errRoutine(`Request Failed! Code: ${status}; Message: ${xhr.statusText}`, xhr);
                break;
            }
        }
    }));
    xhr.send(data);
    preload();
}));

const $ajax = $curl;

const $freezeBtn = (btn, freeze = true, attr = true) => {
    if (freeze === true) {
        $class(btn, "add", "disabled");
        if (attr) $attr(btn, "disabled", "true");
    } else {
        $class(btn, "del", "disabled");
        if (attr) $attr(btn, "disabled", "del");
    }
};

const $frozen = element => !!($class(element, "has", "disabled") || $attr(element, "disabled"));

const $freeze = (element, operation, attr = true) => {
    if (!$frozen(element)) {
        $freezeBtn(element, true, attr);
        operation();
    }
};

"use strict";

/**!
 * Osai Custom Box buils with OMJ$
 * @author Osahenrumwen Aigbogun
 * @version 1.0.3
 * @copyright (c) 2019 Osai LLC | loshq.net/about.
 * @modified 26/08/2022
 */ const $osaiBox = (boxToDraw = "all") => {
    const dialogZindex = 9990;
    const colorVariant = `\n\t\t/*normal variant*/\n\t\t--text: #fffffa;\n\t\t--bg: #1d2124;\n\t\t--link: #009edc;\n\t\t--info: #445ede;\n\t\t--warn: #ffde5c;\n\t\t--fail: #f40204;\n\t\t--fade: #e2e2e2;\n\t\t--success: #0ead69;\n\t\t/*dark variant*/\n\t\t--dark-text: #f5f7fb;\n\t\t--dark-link: #00506e;\n\t\t--dark-info: #3247ac;\n\t\t--dark-warn: #626200;\n\t\t--dark-fail: #a20002;\n\t\t--dark-success: #104e00;\n\t`;
    const ggIcon = `.gg-bell,.gg-bell::before{border-top-left-radius:100px;border-top-right-radius:100px}.gg-bell{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));border:2px solid;border-bottom:0;width:14px;height:14px}.gg-bell::after,.gg-bell::before{content:"";display:block;box-sizing:border-box;position:absolute}.gg-bell::before{background:currentColor;width:4px;height:4px;top:-4px;left:3px}.gg-bell::after{border-radius:3px;width:16px;height:10px;border:6px solid transparent;border-top:1px solid transparent;box-shadow:inset 0 0 0 4px,0 -2px 0 0;top:14px;left:-3px;border-bottom-left-radius:100px;border-bottom-right-radius:100px}.gg-check{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:22px;height:22px;border:2px solid transparent;border-radius:100px}.gg-check::after{content:"";display:block;box-sizing:border-box;position:absolute;left:3px;top:-1px;width:6px;height:10px;border-width:0 2px 2px 0;border-style:solid;transform-origin:bottom left;transform:rotate(45deg)}.gg-check-o{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:22px;height:22px;border:2px solid;border-radius:100px}.gg-check-o::after{content:"";display:block;box-sizing:border-box;position:absolute;left:3px;top:-1px;width:6px;height:10px;border-color:currentColor;border-width:0 2px 2px 0;border-style:solid;transform-origin:bottom left;transform:rotate(45deg)}.gg-bulb{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:16px;height:16px;border:2px solid;border-bottom-color:transparent;border-radius:100px}.gg-bulb::after,.gg-bulb::before{content:"";display:block;box-sizing:border-box;position:absolute}.gg-bulb::before{border-top:0;border-bottom-left-radius:18px;border-bottom-right-radius:18px;top:10px;border-bottom:2px solid transparent;box-shadow:0 5px 0 -2px,inset 2px 0 0 0,inset -2px 0 0 0,inset 0 -4px 0 -2px;width:8px;height:8px;left:2px}.gg-bulb::after{width:12px;height:2px;border-left:3px solid;border-right:3px solid;border-radius:2px;bottom:0;left:0}.gg-danger{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:20px;height:20px;border:2px solid;border-radius:40px}.gg-danger::after,.gg-danger::before{content:"";display:block;box-sizing:border-box;position:absolute;border-radius:3px;width:2px;background:currentColor;left:7px}.gg-danger::after{top:2px;height:8px}.gg-danger::before{height:2px;bottom:2px}.gg-dark-mode{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));border:2px solid;border-radius:100px;width:20px;height:20px}\n\t.gg-close-o{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,.9));width:22px;height:22px;border:2px solid;border-radius:40px}.gg-close-o::after,.gg-close-o::before{content:"";display:block;box-sizing:border-box;position:absolute;width:12px;height:2px;background:currentColor;transform:rotate(45deg);border-radius:5px;top:8px;left:3px}.gg-close-o::after{transform:rotate(-45deg)}\n\t.gg-close{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:22px;height:22px;border:2px solid transparent;border-radius:40px}.gg-close::after,.gg-close::before{content:"";display:block;box-sizing:border-box;position:absolute;width:16px;height:2px;background:currentColor;transform:rotate(45deg);border-radius:5px;top:8px;left:1px}.gg-close::after{transform:rotate(-45deg)}.gg-add-r{box-sizing:border-box;position:relative;display:block;width:22px;height:22px;border:2px solid;transform:scale(var(--ggs,1));border-radius:4px}.gg-add-r::after,.gg-add-r::before{content:"";display:block;box-sizing:border-box;position:absolute;width:10px;height:2px;background:currentColor;border-radius:5px;top:8px;left:4px}.gg-add-r::after{width:2px;height:10px;top:4px;left:8px}.gg-add{box-sizing:border-box;position:relative;display:block;width:22px;height:22px;border:2px solid;transform:scale(var(--ggs,1));border-radius:22px}.gg-add::after,.gg-add::before{content:"";display:block;box-sizing:border-box;position:absolute;width:10px;height:2px;background:currentColor;border-radius:5px;top:8px;left:4px}.gg-add::after{width:2px;height:10px;top:4px;left:8px}.gg-adidas{position:relative;box-sizing:border-box;display:block;width:23px;height:15px;transform:scale(var(--ggs,1));overflow:hidden}\n\t`;
    if (!$in($sel(".osai-gg-icon-abstract"))) $html($sel("head"), "beforeend", `<style class="osai-gg-icon-abstract">.osai-dialogbox,.osai-notifier {${colorVariant} -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; box-sizing: border-box; scroll-behavior: smooth;} ${ggIcon}</style>`);
    let dialog = {}, notifier = {};
    if (boxToDraw === "all" || boxToDraw === "dialog" || boxToDraw === "modal") {
        if (!$in($sel(".osai-dialogbox__present"))) $html($sel("body"), "beforeend", `\n\t\t\t\t<div class="osai-dialogbox">\n\t\t\t\t\t<span style="display: none" class="osai-dialogbox__present"></span>\n\t\t\t\t\t<div class="osai-dialogbox__overlay"></div>\n\t\t\t\t\t<div class="osai-dialogbox__wrapper">\n\t\t\t\t\t\t<div class="osai-dialogbox__header">\n\t\t\t\t\t\t\t<div class="osai-dialogbox__head"></div>\n\t\t\t\t\t\t\t<button class="osai-dialogbox__close-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">\n\t\t\t\t\t\t\t\t<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect>\n\t\t\t\t\t\t\t\t<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect>\n\t\t\t\t\t\t\t</svg></button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class="osai-dialogbox__inner-wrapper">\n\t\t\t\t\t\t\t<div class="osai-dialogbox__body"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class="osai-dialogbox__foot"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>`);
        if (!$in($sel(".osai-dialogbox__stylesheet"))) $html($sel("head"), "beforeend", `<style class="osai-dialogbox__stylesheet" rel="stylesheet" media="all">\n.osai-dialogbox{\nposition: fixed;\nright: 0; left: 0; top: 0; bottom: 0;\ndisplay: block;\nvisibility: hidden;\nopacity: 0;\nz-index: -${dialogZindex};\n}\n.osai-dialogbox__appear{\n\tvisibility: visible;\n\tz-index: ${dialogZindex};\n\topacity: 1;\n}\n.osai-dialogbox__overlay{\n\topacity: .5;\n\tposition: fixed;\n\ttop: 0;bottom: 0;left: 0;right: 0;\n\tbackground: var(--bg);\n\tz-index: 1;\n}\n.osai-dialogbox__wrapper{\n\tdisplay: flex;\n\topacity: 0;\n\tjustify-content: center;\n\talign-items: center;\n\tmax-width: 97vw;\n\tmax-height: 97vh;\n\ttransform: translate(-50%,0);\n\ttop: 50%; left: 50%;\n\tposition: absolute;\n\tz-index: 2;\n\tmargin: auto;\n\tbackground: var(--dark-text);\n\tborder-radius: 5px;\n\tflex-flow: column;\n\ttransition: ease-in-out .8s all;\n\tpadding: 0;\n\toverflow: hidden;\n}\n.osai-dialogbox__header{\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n\twidth: 100%;\n\tpadding: 1.75rem;\n\tborder-bottom: 1px solid #EFF2F5;\n}\n.osai-dialogbox__close-btn{\n\tbackground: transparent;\n\tborder: none;\n\tcolor: var(--dark-info);\n\tfont-weight: 500;\n\tcursor: pointer;\n\toutline: none;\n}\n.osai-dialogbox__close-btn:hover{\n\tcolor: var(--fail);\n}\n.osai-dialogbox__head{\n\tfont-size: 1.15rem;\n\tline-height: 1.15rem;\n\tpadding: 0;\n\tcolor: var(--bg);\n\tmargin: 0;\n\tfont-weight: 600;\n}\n.osai-dialogbox__inner-wrapper{\n\toverflow: auto;\n\tmax-width: 100vw;\n}\n.osai-dialogbox__body{\n\tfont-size: 1rem;\n\tpadding: 1.75rem;\n\tcolor: var(--bg);\n}\n.osai-dialogbox__foot{\n    padding: 1.5rem;\n    border-top: 1px solid #EFF2F5;\n}\n.osai-dialogbox__foot button.success{\n\tbackground: var(--success);\n\tcolor: var(--bg);\n} .osai-dialogbox__foot button.success:hover{\n\tbackground: var(--dark-success);\n\tcolor: var(--dark-text);}\n.osai-dialogbox__foot button.fail{\n\tbackground: var(--fail);\n\tcolor: var(--text);\n}.osai-dialogbox__foot button.fail:hover{\n\tbackground: var(--dark-fail);\n\tcolor: var(--text);}\n.osai-dialogbox__foot button.warn{\n\tbackground: var(--warn);\n\tcolor: var(--text);\n} .osai-dialogbox__foot button.warn:hover{\n\tbackground: var(--dark-warn);\n\tcolor: var(--text);}\n.osai-dialogbox__foot button.info{\n\tbackground: var(--info);\n\tcolor: var(--dark-text);\n} .osai-dialogbox__foot button.info:hover{\n\tbackground: var(--dark-info);\n\tcolor: var(--text);}\n.osai-dialogbox__foot button.link{\n\tbackground: var(--link);\n\tcolor: var(--dark-text);\n} .osai-dialogbox__foot button.link:hover{\n\tbackground: var(--dark-link);\n\tcolor: var(--text);}\n\t.osai-dialogbox__foot button.success i,.osai-dialogbox__foot button.fail i, .osai-dialogbox__foot button.warn i, .osai-dialogbox__foot button.info i,.osai-dialogbox__foot button.link i{\n    color: var(--dark-text)\n}\n/* disable scrolling when modal is opened */\n.osai-modal__open{\n\toverflow-y: hidden;\n\tscroll-behavior: smooth;\n}\n.osai-modal__appear{\n\topacity: 1;\n\ttransform: translate(-50%,-50%);\n}\n.osai-modal__btn{\n\tborder-radius: .755rem;\n\tborder: solid 1px transparent;\n\tpadding: 0.65rem 1.73rem;\n\tcursor: pointer;\n\toutline: none;\n\ttransition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;\n\tbackground-color: var(--bg);\n\tcolor: var(--text);\n\tdisplay: inline-flex;\n\tjustify-content: center;\n\talign-items: center;\n}\n@media screen and (max-width: 600px){\n\t.osai-dialogbox__wrapper{\n\t\tmin-width: 90vw;\n\t\tmax-width: 95vw;\n\t\tmax-height: 90vh;\n\t}\n}\n</style>`);
        const BOX = $sel(".osai-dialogbox");
        const BOX_OVERLAY = $sel(".osai-dialogbox__overlay");
        const BOX_WRAPPER = $sel(".osai-dialogbox__wrapper");
        const BOX_HEADER = $sel(".osai-dialogbox__header");
        const BOX_CLOSE_BTN = $sel(".osai-dialogbox__close-btn");
        const BOX_INNER_WRAPPER = $sel(".osai-dialogbox__inner-wrapper");
        const BOX_HEAD = $sel(".osai-dialogbox__head");
        const BOX_BODY = $sel(".osai-dialogbox__body");
        const BOX_FOOT = $sel(".osai-dialogbox__foot");
        const BOX_PRESENCE = $sel(".osai-dialogbox__present");
        const BOX_VIEW = (act = "close") => {
            if (act === "close") {
                $class(BOX_WRAPPER, "del", "osai-modal__appear");
                setTimeout((() => {
                    $class(BOX, "del", "osai-dialogbox__appear");
                    $class($sel("html"), "del", "osai-modal__open");
                }), 200);
            }
            if (act === "open") {
                $class($sel("html"), "add", "osai-modal__open");
                $class(BOX, "add", "osai-dialogbox__appear");
                setTimeout((() => $class(BOX_WRAPPER, "add", "osai-modal__appear")), 100);
            }
        };
        const BOX_SIZE = size => {
            switch (size) {
              case "xs":
                BOX_INNER_WRAPPER.style.minWidth = "30vw";
                break;

              case "sm":
                BOX_INNER_WRAPPER.style.minWidth = "45vw";
                break;

              case "md":
                BOX_INNER_WRAPPER.style.minWidth = "60vw";
                break;

              case "lg":
                BOX_INNER_WRAPPER.style.minWidth = "75vw";
                break;

              case "xl":
                BOX_INNER_WRAPPER.style.minWidth = "90vw";
                break;

              case "xxl":
                BOX_INNER_WRAPPER.style.minWidth = "99vw";
                break;

              default:
                let configSelector = config => $sel("input[data-config='" + config + "'].osai-dialogbox__config");
                if (configSelector("box-size") && $data(configSelector("box-size"), "value") !== "undefined") BOX_SIZE($data(configSelector("box-size"), "value")); else BOX_INNER_WRAPPER.style.minWidth = "60vw";
                break;
            }
        };
        const BOX_RENDER = (closeOnBlur, size, align, onClose, then) => {
            let configSelector = config => $sel("input[data-config='" + config + "'].osai-dialogbox__config");
            BOX_VIEW("open");
            BOX_SIZE(size);
            if (configSelector("main-wrapper")) $style(BOX_WRAPPER, $data(configSelector("main-wrapper"), "value"));
            if (configSelector("box-z-index")) $style(BOX).zIndex = $data(configSelector("box-z-index"), "value");
            if (configSelector("head")) $style(BOX_HEAD, $data(configSelector("head"), "value"));
            if (configSelector("close")) $style(BOX_CLOSE_BTN, $data(configSelector("close"), "value"));
            if (configSelector("foot")) $style(BOX_FOOT, $data(configSelector("foot"), "value"));
            if (align) $style(BOX_BODY).textAlign = align; else if (configSelector("box-body-align")) $style(BOX_BODY).textAlign = $data(configSelector("box-body-align"), "value"); else $style(BOX_BODY).textAlign = "inherit";
            if (configSelector("body")) $style(BOX_BODY, $data(configSelector("body"), "value"));
            if (!closeOnBlur) {
                let overlayClose = configSelector("close-on-blur") ? $data(configSelector("close-on-blur"), "value") : undefined;
                if ($type(overlayClose) === "String") closeOnBlur = JSON.parse(overlayClose); else if ($type(overlayClose) === "Boolean") closeOnBlur = overlayClose;
            }
            let closeHandler = () => BOX_CLOSE(onClose);
            if ($html(BOX_FOOT).trim() === "") $style(BOX_FOOT, "display:none");
            if ($html(BOX_HEAD).trim() === "") {
                $style(BOX_HEADER, "display:none");
            }
            if (closeOnBlur === false) $on(BOX_OVERLAY, "click", closeHandler, "del"); else $on(BOX_OVERLAY, "click", closeHandler);
            $on(BOX_CLOSE_BTN, "click", closeHandler, "on");
            $on(BOX_WRAPPER, "click", (e => {
                if ($class(e.target, "has", "osai-close-box") || $class(e.target.parentNode, "has", "osai-close-box")) {
                    e.preventDefault();
                    closeHandler();
                }
            }), "on");
            $on($doc, "keydown", (e => {
                if (e.keyCode === 27) {
                    e.preventDefault();
                    closeHandler();
                }
            }), "on");
            $drag(BOX, BOX_HEADER);
            if (then) then();
        };
        const BOX_FLUSH = (where = "*") => {
            $style(BOX_HEADER, "del");
            switch (where) {
              case "head":
                $html(BOX_HEAD, "in", "");
                $style(BOX_HEAD, "del");
                break;

              case "body":
                $html(BOX_BODY, "in", "");
                $style(BOX_BODY, "del");
                break;

              case "foot":
                $html(BOX_FOOT, "in", "");
                $style(BOX_FOOT, "del");
                break;

              default:
                $html(BOX_HEAD, "in", "");
                $html(BOX_BODY, "in", "");
                $html(BOX_FOOT, "in", "");
                $style(BOX_WRAPPER, "del");
                $style(BOX_HEAD, "del");
                $style(BOX_BODY, "del");
                $style(BOX_FOOT, "del");
                break;
            }
            return this;
        };
        const BOX_INSERT = (where, text = "") => {
            switch (where) {
              case "head":
                where = BOX_HEAD;
                $style(BOX_HEAD, "del");
                break;

              case "body":
                where = BOX_BODY;
                break;

              case "foot":
                where = BOX_FOOT;
                break;

              case "head+":
                where = BOX_HEAD;
                $style(BOX_HEAD, "del");
                text = $html(BOX_HEAD) + text;
                break;

              case "body+":
                where = BOX_BODY;
                text = $html(BOX_BODY) + text;
                break;

              case "foot+":
                where = BOX_FOOT;
                text = $html(BOX_FOOT) + text;
                break;

              default:
                return;
            }
            $html(where, "in", text);
        };
        const BOX_CLOSE = callbackFn => {
            BOX_VIEW("close");
            setTimeout((() => BOX_FLUSH()), 250);
            if ($type(callbackFn) === "Function") callbackFn();
            return false;
        };
        const BOX_ACTION = (actionFunction, closeOnDone = true, onClose = (() => null)) => {
            actionFunction();
            if (closeOnDone) BOX_CLOSE(onClose);
        };
        dialog = {
            render: (...args) => {
                BOX_RENDER(...args);
                return dialog;
            },
            flush: (where = "*") => {
                BOX_FLUSH(where);
                return dialog;
            },
            get: {
                box: BOX,
                head: BOX_HEAD,
                foot: BOX_FOOT,
                wrapper: BOX_INNER_WRAPPER,
                wrap: BOX_WRAPPER,
                body: BOX_BODY
            },
            config: ({align: align, size: size, closeOnBlur: closeOnBlur, wrapper: wrapper, head: head, foot: foot, body: body, close: close, zIndex: zIndex}) => {
                let addConfig = (config, value) => {
                    let element = config => $sel("input[data-config='" + config + "'].osai-dialogbox__config");
                    if (!element(config)) $html(BOX_PRESENCE, "beforeend", `<input type="hidden" class="osai-dialogbox__config" data-config="${config}" data-value="${value}">`); else $data(element(config), "value", value);
                };
                if (align) addConfig("box-body-align", align);
                if (size) addConfig("box-size", size);
                if (wrapper) addConfig("main-wrapper", wrapper);
                if (head) addConfig("head", head);
                if (body) addConfig("body", body);
                if (foot) addConfig("foot", foot);
                if (close) addConfig("close", close);
                if (zIndex) addConfig("box-z-index", zIndex);
                if ($type(closeOnBlur) === "String" || $type(closeOnBlur) === "Boolean") addConfig("close-on-blur", closeOnBlur);
            },
            insert: (where, text = "") => {
                BOX_INSERT(where, text);
                return dialog;
            },
            closeBox: (onClose = (() => null)) => {
                BOX_CLOSE(onClose);
                return dialog;
            },
            action: (operation, closeOnDone = true) => BOX_ACTION(operation, closeOnDone)
        };
    }
    if (boxToDraw === "all" || boxToDraw === "notifier" || boxToDraw === "notify") {
        if (!$in($sel(".osai-simple-notifier"))) $html($sel("body"), "beforeend", `<div class="osai-simple-notifier"><div style="display: none" class="osai-notifier__config_wrapper"></div></div>`);
        if (!$in($sel(".osai-notifier__stylesheet"))) $html($sel("head"), "beforeend", `<style class="osai-notifier__stylesheet" rel="stylesheet" media="all">\n\t\t\t.osai-notifier{\n\t\t\t\tscroll-behavior: smooth;\n\t\t\t\tposition: fixed;\n\t\t\t\ttop: 10px;\n\t\t\t\tright: 10px;\n\t\t\t\tborder-radius: 5px;\n\t\t\t\tpadding: 10px;\n\t\t\t\tfont-weight: 500;\n\t\t\t\tcolor: var(--dark-text);\n\t\t\t\tbackground-color: var(--dark-info);\n\t\t\t\tbox-shadow: 1px 2px 4px 0 var(--bg);\n\t\t\t\tdisplay:flex;\n\t\t\t\topacity: 0;\n\t\t\t\ttransform: translate(0,-50%);\n\t\t\t\tz-index: 9993;\n\t\t\t\tmin-height: 50px;\n\t\t\t\tmin-width: 150px;\n\t\t\t\tjustify-content: space-between;\n\t\t\t\talign-items: flex-start;\n                transition: ease-in-out all .8s;\n\t\t\t}\n\t\t\t.osai-notifier__display{\n\t\t\t\topacity: 1;\n\t\t\t\ttransform: translate(0,0);\n\t\t\t\tmax-width: 50vw;\n\t\t\t}\n\t\t\t.osai-notifier__display-center{\n\t\t\t\ttop: 50%; \n\t\t\t\tleft: 50%;\n                right: auto;\n\t\t\t\ttransform: translate(-50%,-50%);\n\t\t\t} @media (max-width: 767px){\n                .osai-notifier__display-center{\n                    max-width: 60vw;\n                }\n            }\n            @media (max-width: 426px){\n            \t.osai-notifier__display-center{\n                    max-width: 93vw;\n                }\n                .osai-notifier__display{\n\t\t\t\t\tmax-width: 93vw;\n\t\t\t\t}\n            }\n\t\t\t.osai-notifier__close{\n\t\t\t\tposition: absolute;\n\t\t\t\tright: 10px;\n\t\t\t\ttop: 10px;\n\t\t\t\tcursor: pointer;\n\t\t\t\topacity: .8;\n\t\t\t}\n\t\t\t.osai-notifier__close:hover{\n\t\t\t\topacity: 1;\n\t\t\t\tcolor: var(--fail);\n\t\t\t}\n\t\t\t.osai-notifier.success,.osai-notifier.fail,.osai-notifier.warn,.osai-notifier.info{\n\t\t\t\tcolor: var(--dark-text);\n\t\t\t}\n\t\t\t.osai-notifier.success{\n\t\t\t\tbackground-color: var(--success);\n\t\t\t}\n\t\t\t.osai-notifier.fail{\n\t\t\t\tbackground-color: var(--fail);\n\t\t\t}\n\t\t\t.osai-notifier.warn{\n\t\t\t\tbackground-color: var(--warn);\n\t\t\t\tcolor: var(--bg);\n\t\t\t}\n\t\t\t.osai-notifier.info{\n\t\t\t\tbackground-color: var(--info);\n\t\t\t}\n\t\t\t.osai-notifier__body{\n\t\t\t\tpadding: 5px 26px 5px 36px;\n\t\t\t\tpadding-left: 0;\n\t\t\t\ttext-align: center;\n\t\t\t\twidth: 100%;\n\t\t\t}\n\t\t</style>`);
        let presenceSelector = ".osai-simple-notifier";
        let sideCardSelector = ".osai-notifier-entry:not(.osai-notifier__display-center)";
        const NOTIFY = (dialog, theme, options) => {
            if (!$in($sel(presenceSelector))) {
                console.error("Omj$ Notifier could not be found, you probably didn't draw it's box");
                return false;
            }
            let configSelector = config => {
                let x = $sel("input.osai-notifier__config[data-config='" + config + "']", $sel(presenceSelector));
                if (x) return $data(x, "value");
                return null;
            };
            dialog = dialog ?? configSelector("message") ?? "Simple notifier by Lay's Omj$";
            theme = theme ?? configSelector("type");
            options = options ?? {};
            let styleClass = "";
            let postStyle = "";
            let position = options.position ?? configSelector("position") ?? "side";
            let uniqueId = options.id ? `id="${options.id}"` : "";
            let duration = parseInt(options.duration ?? configSelector("duration") ?? 5e3);
            let defaultTopMargin = configSelector("margin") ?? 10;
            let previousEntryHeight = 0;
            let getNextEntryTop = () => {
                let nextTop = 0;
                $loop($sela(sideCardSelector), (entry => nextTop += Math.floor(entry.offsetHeight + defaultTopMargin)));
                return nextTop;
            };
            let getMyLastSideEntryTopSide = currentEntry => {
                let mySibling = currentEntry.previousElementSibling;
                if (mySibling === $sel(".osai-notifier__config_wrapper", $sel(presenceSelector))) return null;
                if ($class(mySibling, "has", "osai-notifier__display-center")) return getMyLastSideEntryTopSide(mySibling);
                if ($class(mySibling, "has", "osai-notifier-entry")) return mySibling;
                return null;
            };
            let getMyLastSideEntryDownSide = currentEntry => {
                try {
                    let mySibling = currentEntry.nextElementSibling;
                    if ($class(mySibling, "has", "osai-notifier__display-center")) return getMyLastSideEntryDownSide(mySibling);
                    if ($class(mySibling, "has", "osai-notifier-entry")) return mySibling;
                } catch (e) {}
                return null;
            };
            let adjustEntries = (currentEntry, entrySibling, closed = false) => {
                if (!currentEntry || !entrySibling || currentEntry === entrySibling || !$class(entrySibling, "has", "osai-notifier__display")) return;
                let x = getMyLastSideEntryTopSide(currentEntry);
                if (!x && $class(entrySibling, "has", "osai-notifier__display-center")) entrySibling = getMyLastSideEntryDownSide(entrySibling);
                if (closed && x) {
                    currentEntry = x;
                    closed = false;
                }
                let newTop = closed ? 0 : parseInt($style(currentEntry).top.replace("px", "")) + currentEntry.offsetHeight;
                placeNewEntry(entrySibling, null, newTop + defaultTopMargin);
                adjustEntries(entrySibling, entrySibling.nextElementSibling);
            };
            let removeEntry = (entry, useDuration = true, closeEntry = false) => {
                if (closeEntry) {
                    adjustEntries(entry, entry.nextElementSibling, true);
                    setTimeout((() => entry.remove()), 100);
                }
                if (duration === "pin" || duration === "fixed" || duration === -1) return;
                if (useDuration) {
                    setTimeout((() => $class(entry, "del", "osai-notifier__display")), duration);
                    duration = duration + 50;
                }
                setTimeout((() => {
                    adjustEntries(entry, entry.nextElementSibling, true);
                    setTimeout((() => entry.remove()), 100);
                }), duration);
            };
            let placeNewEntry = (entry, oldEntryHeight, useThisHeight = null) => {
                $on($sel(".osai-notifier__close", entry), "click", (e => {
                    e.preventDefault();
                    removeEntry(entry, false, true);
                }));
                removeEntry(entry);
                if (!useThisHeight && $class(entry, "has", "osai-notifier__display-center")) return;
                if (useThisHeight) return $style(entry, `top:${useThisHeight}px`);
                oldEntryHeight = parseInt(oldEntryHeight);
                let currentTop = oldEntryHeight + defaultTopMargin;
                if (oldEntryHeight === 0) return $style(entry, "top:10px");
                $style(entry, "top:" + currentTop + "px");
            };
            if (position === "center") postStyle = "osai-notifier__display-center";
            if ($sel(sideCardSelector)) previousEntryHeight = getNextEntryTop();
            switch (theme) {
              case "success":
              case "good":
                styleClass = "success";
                break;

              case "fail":
              case "danger":
              case "error":
                styleClass = "fail";
                break;

              case "info":
                styleClass = "info";
                break;

              case "warn":
              case "warning":
                styleClass = "warn";
                break;
            }
            $html($sel(presenceSelector), "beforeend", `<div class="osai-notifier osai-notifier-entry ${postStyle} ${styleClass}" ${uniqueId}><div class="osai-notifier__body">${dialog}</div><div class="osai-notifier__close"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect><rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect></svg></div></div>`);
            let notifyEntry = $sela(".osai-notifier-entry");
            let currentEntry = $end(notifyEntry);
            setTimeout((() => {
                $class(currentEntry, "add", "osai-notifier__display");
                placeNewEntry(currentEntry, previousEntryHeight);
            }), 200);
            return currentEntry;
        };
        const CONFIG = $sel(".osai-notifier__config_wrapper");
        notifier = {
            notify: (dialog, theme, option) => NOTIFY(dialog, theme, option),
            notifyConfig: ({duration: duration, type: type, position: position, message: message, margin: margin}) => {
                let addConfig = (config, value) => {
                    if (!value) return;
                    let element = config => $sel("input[data-config='" + config + "'].osai-notifier__config_wrapper");
                    if (!element(config)) $html(CONFIG, "beforeend", `<input type="hidden" class="osai-notifier__config" data-config="${config}" data-value="${value}">`); else $data(element(config), "value", value);
                };
                addConfig("duration", duration);
                addConfig("type", type);
                addConfig("position", position);
                addConfig("margin", margin);
                addConfig("message", message?.replaceAll('"', "'"));
            }
        };
    }
    return {
        ...dialog,
        ...notifier
    };
};

const CusWind = $osaiBox();

function aMsg(message, option = {
    head: "Alert Box",
    showButton: true,
    closeOnBlur: null,
    size: "sm",
    align: null,
    onClose: null
}) {
    CusWind.insert("body", message);
    if (option.showButton === false) CusWind.flush("head").flush("foot"); else CusWind.insert("head", option.head).insert("foot", `<button type="button" class="success osai-modal__btn osai-close-box"><i class='gg-check'></i></button>`);
    CusWind.render(option.closeOnBlur, option.size, option.align, option.onClose);
}

function cMsg(message, operation, option = {
    closeOnDone: true,
    closeOnBlur: null,
    size: "sm",
    align: null,
    onClose: () => null
}) {
    CusWind.insert("head", "Confirmation Box").insert("body", message).insert("foot", `<button type="button" class="success osai-modal__btn osai-confirm-success"><i class="gg-check"></i></button>\n\t\t<button type="button" class="fail osai-modal__btn osai-close-box"><i class="gg-close"></i></button>`).render(option.closeOnBlur, option.size, option.align, option.onClose);
    $on($sel(".osai-confirm-success"), "click", (e => {
        e.preventDefault();
        CusWind.action(operation, option.closeOnDone);
    }));
}

function pMsg(message = "Prompt Box", operation = (inputValue => inputValue), custom = {
    body: null,
    operation: null,
    closeOnDone: true,
    closeOnBlur: null,
    size: null,
    align: null,
    onClose: () => null
}) {
    CusWind.insert("head", "Prompt Box").insert("body", "<div style='margin-bottom: 5px'>" + message + "</div>").insert("body+", custom.body || "<textarea class='osai-prompt-input-box' style='width: 100%; height: 50px; text-align: center' placeholder='Type in...'></textarea>").insert("foot", `<button type="button" class="success osai-modal__btn osai-confirm-success"><i class="gg-check"></i></button>\n\t\t<button type="button" class="fail osai-close-box osai-modal__btn"><i class="gg-close"></i></button>`).render(custom.closeOnBlur, custom.size, custom.align, custom.onClose);
    $on($sel(".osai-confirm-success"), "click", (e => {
        e.preventDefault();
        if ($sel(".osai-prompt-input-box")) {
            if ($sel(".osai-prompt-input-box").value) CusWind.action((() => operation($sel(".osai-prompt-input-box").value)), custom.closeOnDone);
        } else CusWind.action(custom.operation, custom.closeOnDone);
    }));
}

function osModal(option = {}) {
    option = {
        head: option.head ?? "osModal",
        body: option.body ?? "Osai Modal Box Built With OMJ$",
        foot: option.foot ?? null,
        operation: option.operation ? option.operation : () => null,
        onClose: option.onClose ? option.onClose : () => null,
        then: option.then ? option.then : () => null,
        append: option.append ?? false,
        closeOnBlur: option.closeOnBlur,
        size: option.size,
        align: option.align
    };
    CusWind.insert("head", option.head).insert(option.append === true ? "body+" : "body", option.body).insert("foot", option.foot !== null ? option.foot : `<button type="button" class="fail osai-close-box osai-modal__btn"><i class="gg-close-o"></i></button>`).render(option.closeOnBlur, option.size, option.align, option.onClose);
    return option.operation() ?? option.then();
}

function osNote(message, type, option) {
    CusWind.notify(message, type, option);
}