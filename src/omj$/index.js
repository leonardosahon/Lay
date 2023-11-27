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

let $store;

try {
    $store = $win.localStorage;
} catch (e) {}

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
    const option = options[0] ?? "on";
    const multipleElement = element.length && !(element.type === "select-one" || element.type === "select-multiple");
    try {
        const addListener = (listenerElement, index) => {
            let listenerFn = e => listener(e, multipleElement ? element[index] : element, index, ...options);
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
        if (multipleElement) return $loop(element, ((ele, i) => addListener(ele, i)));
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
    const infinity = operation.infinite ?? false;
    const fun = operation.then ?? operation;
    let previousOutput = "";
    for (let key in obj) {
        if (!obj.hasOwnProperty(key)) continue;
        key = $isInt(key);
        prop.length++;
        prop.last.key = key;
        prop.last.value = obj[key];
        if (prop.length === 1) {
            prop.first.key = key;
            prop.first.value = obj[key];
        }
        const returnValue = fun(obj[key], key, previousOutput);
        if (returnValue === "continue") continue;
        if (returnValue === "break") break;
        prop.output = returnValue ?? null;
        previousOutput = prop.output;
        prop.outputType = $type(prop.output);
        if (infinity === false && prop.length > 999) $omjsError("$loop", "Infinite loop detected, process was ended prematurely to save resources. Please pass `infinite: true` if you intend for the loop to go beyond '1000' iterations", true);
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

const $img2blob = img => {
    const canvas = $doc.createElement("canvas");
    const context = canvas.getContext("2d");
    canvas.width = img.width;
    canvas.height = img.height;
    context.drawImage(img, 0, 0);
    return canvas.toDataURL("image/png");
};

const $media = ({srcElement: srcElement, previewElement: previewElement, then: then = null, on: on = "change", useReader: useReader = true}) => {
    const currentMediaSrc = previewElement.src;
    let previewMedia = srcElement => {
        let srcProcessed = [];
        switch (srcElement.type) {
          default:
            previewElement.src = srcElement.value !== "" ? srcElement.value : currentMediaSrc;
            break;

          case "file":
            if (useReader) {
                const reader = new FileReader;
                $on(reader, "load", (() => {
                    if (srcElement.value === "") return previewElement.src = currentMediaSrc;
                    previewElement.src = reader.result;
                    then && then(reader.result);
                }), "on");
                if (srcElement.files[0]) return reader.readAsDataURL(srcElement.files[0]);
                previewElement.src = currentMediaSrc;
            }
            if (srcElement.multiple) return osNote("Media preview doesn't support preview for multiple files");
            if (srcElement.value === "") return previewElement.src = currentMediaSrc;
            srcProcessed = URL.createObjectURL(srcElement.files[0]);
            previewElement.src = srcProcessed;
            then && then(srcProcessed);
            break;
        }
    };
    if (!on) return previewMedia(srcElement);
    if ($type(srcElement) !== "Array") return $on(srcElement, on, (() => previewMedia(srcElement)), "on");
    $loop(srcElement, (src => $on(src, on, (() => previewMedia(src)), "on")));
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
    if (elementAnchor) {
        elementAnchor.onmousedown = dragMouseDown;
        elementAnchor.ontouchstart = dragMouseDown;
    } else {
        element.onmousedown = dragMouseDown;
        element.ontouchstart = dragMouseDown;
    }
    function dragMouseDown(e) {
        e.preventDefault();
        let touchType = e.touches ? e.touches[0] : e;
        pos3 = touchType.clientX;
        pos4 = touchType.clientY;
        $on($doc, "mouseup,touchend", (() => {
            $doc.onmouseup = null;
            $doc.onmousemove = null;
            $doc.ontouchend = null;
            $doc.ontouchmove = null;
        }));
        $on($doc, "mousemove,touchmove", (e => {
            touchType = e.touches ? e.touches[0] : e;
            pos1 = pos3 - touchType.clientX;
            pos2 = pos4 - touchType.clientY;
            pos3 = touchType.clientX;
            pos4 = touchType.clientY;
            element.style.top = element.offsetTop - pos2 + "px";
            element.style.left = element.offsetLeft - pos1 + "px";
        }));
    }
};

const $preloader = (act = "show") => {
    if (!$sel(".osai-preloader")) $html($sel("body"), "beforeend", `<div class="osai-preloader" style="display:none"><svg width="110" height="110" viewBox="0 0 110 110" fill="none" xmlns="http://www.w3.org/2000/svg">\n<path d="M33.7 0.419922C32.6768 0.428607 31.6906 0.803566 30.9201 1.47684C30.1496 2.15011 29.6458 3.07715 29.5 4.08992C28.5259 10.0187 25.4776 15.4087 20.8988 19.2989C16.32 23.1891 10.5083 25.3265 4.5 25.3299C3.37445 25.3352 2.2965 25.7846 1.50061 26.5805C0.704714 27.3764 0.25526 28.4544 0.25 29.5799V86.0499C0.25 89.2017 0.87078 92.3225 2.07689 95.2343C3.28301 98.1461 5.05083 100.792 7.27944 103.02C9.50804 105.249 12.1538 107.017 15.0656 108.223C17.9774 109.429 21.0983 110.05 24.25 110.05H75.6C76.6302 110.034 77.6202 109.647 78.388 108.96C79.1559 108.273 79.6501 107.332 79.78 106.31C80.584 100.96 83.0765 96.007 86.8938 92.1735C90.7112 88.34 95.6536 85.8266 101 84.9999C103.562 84.6132 106.168 84.6132 108.73 84.9999H109.73V24.4499C109.73 18.0847 107.201 11.9802 102.701 7.47936C98.1997 2.97849 92.0952 0.449923 85.73 0.449923L33.7 0.419922ZM61.57 79.9099H47.73C45.379 79.9099 43.051 79.4465 40.8792 78.5462C38.7074 77.6459 36.7343 76.3264 35.0728 74.663C33.4113 72.9996 32.0939 71.0251 31.1961 68.8523C30.2982 66.6794 29.8374 64.351 29.84 61.9999V46.7499C29.8387 44.6348 30.2542 42.5401 31.0627 40.5856C31.8712 38.6312 33.0569 36.8551 34.552 35.359C36.0472 33.863 37.8225 32.6761 39.7765 31.8664C41.7305 31.0567 43.8249 30.6399 45.94 30.6399H63.36C67.6326 30.6399 71.7303 32.3372 74.7515 35.3584C77.7727 38.3796 79.47 42.4773 79.47 46.7499V61.9999C79.4713 64.3514 79.0093 66.6801 78.1103 68.853C77.2113 71.0259 75.8931 73.0004 74.2308 74.6636C72.5685 76.3268 70.5947 77.6462 68.4223 78.5464C66.25 79.4466 63.9215 79.9099 61.57 79.9099Z" fill="url(#paint0_linear_29_21)"/>\n<defs>\n<linearGradient id="paint0_linear_29_21" x1="8.59" y1="74.8799" x2="109.29" y2="31.9699" gradientUnits="userSpaceOnUse">\n<stop stop-color="#53C3BD"/>\n<stop offset="0.47" stop-color="#739CD2"/>\n<stop offset="1" stop-color="#4C64AF"/>\n</linearGradient>\n</defs>\n</svg>\n<span>Loading...please wait</span></div></div>`);
    if (!$sel(".osai-preloader-css")) $html($sel("head"), "beforeend", `<style type="text/css" class="osai-preloader-css">.osai-preloader{display: flex;position: fixed; flex-direction:column;width: 101vw;height: 101vh;justify-content: center;align-items: center;background: rgba(8,11,31,0.8);left: -5px;right: -5px;top: -5px;bottom: -5px;z-index:9993}.osai-preloader__container{display: table; text-align: center;margin:0;padding:0;}.osai-preloader svg{width: 80px;padding: 1px;border-radius: 5px;animation: pulse 2s infinite linear;transition: .6s ease-in-out}.osai-preloader span{color: #fff;text-align: center;margin-top: 10px;display: block}@keyframes pulse {0% {transform: scale(0.6);opacity: 0}33% {transform: scale(1);opacity: 1}100%{transform: scale(1.4);opacity: 0}}</style>`);
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
    let headers = option.headers ?? {};
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
                if (alert_error) alert(msg); else osNote(msg, "fail", {
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
                response = method === "HEAD" ? xhr : xhr.responseText ?? xhr.response;
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
    if (data) {
        switch ($type(data)) {
          case "String":
          case "Object":
          case "FormData":
            break;

          case "File":
            type = "file";
            let x = data;
            data = new FormData;
            data.append("file", x);
            break;

          default:
            data = $getForm(data, true);
            if (data.hasFile) {
                data = data.file;
                type = "file";
            } else data = type === "json" ? data.object : data.string;
            break;
        }
    }
    if (option.xhrSetup) option.xhrSetup(xhr);
    let requestHeader = "application/x-www-form-urlencoded";
    switch (type) {
      default:
        break;

      case "file":
        requestHeader = null;
        break;

      case "json":
        requestHeader = method === "get" ? requestHeader : "application/json";
        data = JSON.stringify(data);
        break;

      case "text":
        let x = data;
        if ($type(data) === "Object") {
            x = "";
            $loop(data, ((value, name) => x += name + "=" + value + "&"));
        }
        data = x?.replace(/&+$/, "");
        break;

      case "xml":
        requestHeader = method !== "GET" ? "text/xml" : requestHeader;
        break;

      case "custom":
        requestHeader = method !== "GET" ? content : requestHeader;
        break;
    }
    requestHeader && xhr.setRequestHeader("Content-Type", requestHeader);
    $loop(headers, ((value, key) => xhr.setRequestHeader(key, value)));
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
 * @version 1.4
 * @copyright (c) 2019 Osai Technologies LLC.
 * @modified 18/09/2022
 */ const $osaiBox = (boxToDraw = "all") => {
    const dialogZindex = 9990;
    const colorVariant = `\n\t\t/*normal variant*/\n\t\t--text: #fffffa;\n\t\t--bg: #1d2124;\n\t\t--link: #009edc;\n\t\t--info: #445ede;\n\t\t--warn: #ffde5c;\n\t\t--fail: #f40204;\n\t\t--fade: #e2e2e2;\n\t\t--success: #0ead69;\n\t\t/*dark variant*/\n\t\t--dark-text: #f5f7fb;\n\t\t--dark-link: #00506e;\n\t\t--dark-info: #3247ac;\n\t\t--dark-warn: #626200;\n\t\t--dark-fail: #a20002;\n\t\t--dark-success: #104e00;\n\t`;
    const ggIcon = `.gg-bell,.gg-bell::before{border-top-left-radius:100px;border-top-right-radius:100px}.gg-bell{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));border:2px solid;border-bottom:0;width:14px;height:14px}.gg-bell::after,.gg-bell::before{content:"";display:block;box-sizing:border-box;position:absolute}.gg-bell::before{background:currentColor;width:4px;height:4px;top:-4px;left:3px}.gg-bell::after{border-radius:3px;width:16px;height:10px;border:6px solid transparent;border-top:1px solid transparent;box-shadow:inset 0 0 0 4px,0 -2px 0 0;top:14px;left:-3px;border-bottom-left-radius:100px;border-bottom-right-radius:100px}.gg-check{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:22px;height:22px;border:2px solid transparent;border-radius:100px}.gg-check::after{content:"";display:block;box-sizing:border-box;position:absolute;left:3px;top:-1px;width:6px;height:10px;border-width:0 2px 2px 0;border-style:solid;transform-origin:bottom left;transform:rotate(45deg)}.gg-check-o{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:22px;height:22px;border:2px solid;border-radius:100px}.gg-check-o::after{content:"";display:block;box-sizing:border-box;position:absolute;left:3px;top:-1px;width:6px;height:10px;border-color:currentColor;border-width:0 2px 2px 0;border-style:solid;transform-origin:bottom left;transform:rotate(45deg)}.gg-bulb{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:16px;height:16px;border:2px solid;border-bottom-color:transparent;border-radius:100px}.gg-bulb::after,.gg-bulb::before{content:"";display:block;box-sizing:border-box;position:absolute}.gg-bulb::before{border-top:0;border-bottom-left-radius:18px;border-bottom-right-radius:18px;top:10px;border-bottom:2px solid transparent;box-shadow:0 5px 0 -2px,inset 2px 0 0 0,inset -2px 0 0 0,inset 0 -4px 0 -2px;width:8px;height:8px;left:2px}.gg-bulb::after{width:12px;height:2px;border-left:3px solid;border-right:3px solid;border-radius:2px;bottom:0;left:0}.gg-danger{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:20px;height:20px;border:2px solid;border-radius:40px}.gg-danger::after,.gg-danger::before{content:"";display:block;box-sizing:border-box;position:absolute;border-radius:3px;width:2px;background:currentColor;left:7px}.gg-danger::after{top:2px;height:8px}.gg-danger::before{height:2px;bottom:2px}.gg-dark-mode{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));border:2px solid;border-radius:100px;width:20px;height:20px}\n\t.gg-close-o{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,.9));width:22px;height:22px;border:2px solid;border-radius:40px}.gg-close-o::after,.gg-close-o::before{content:"";display:block;box-sizing:border-box;position:absolute;width:12px;height:2px;background:currentColor;transform:rotate(45deg);border-radius:5px;top:8px;left:3px}.gg-close-o::after{transform:rotate(-45deg)}\n\t.gg-close{box-sizing:border-box;position:relative;display:block;transform:scale(var(--ggs,1));width:22px;height:22px;border:2px solid transparent;border-radius:40px}.gg-close::after,.gg-close::before{content:"";display:block;box-sizing:border-box;position:absolute;width:16px;height:2px;background:currentColor;transform:rotate(45deg);border-radius:5px;top:8px;left:1px}.gg-close::after{transform:rotate(-45deg)}.gg-add-r{box-sizing:border-box;position:relative;display:block;width:22px;height:22px;border:2px solid;transform:scale(var(--ggs,1));border-radius:4px}.gg-add-r::after,.gg-add-r::before{content:"";display:block;box-sizing:border-box;position:absolute;width:10px;height:2px;background:currentColor;border-radius:5px;top:8px;left:4px}.gg-add-r::after{width:2px;height:10px;top:4px;left:8px}.gg-add{box-sizing:border-box;position:relative;display:block;width:22px;height:22px;border:2px solid;transform:scale(var(--ggs,1));border-radius:22px}.gg-add::after,.gg-add::before{content:"";display:block;box-sizing:border-box;position:absolute;width:10px;height:2px;background:currentColor;border-radius:5px;top:8px;left:4px}.gg-add::after{width:2px;height:10px;top:4px;left:8px}.gg-adidas{position:relative;box-sizing:border-box;display:block;width:23px;height:15px;transform:scale(var(--ggs,1));overflow:hidden}\n\t`;
    if (!$in($sel(".osai-gg-icon-abstract"))) $html($sel("head"), "beforeend", `<style class="osai-gg-icon-abstract">.osai-dialogbox,.osai-notifier {${colorVariant} -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; box-sizing: border-box; scroll-behavior: smooth;} ${ggIcon}</style>`);
    let dialog = {}, notifier = {};
    if (boxToDraw === "all" || boxToDraw === "dialog" || boxToDraw === "modal") {
        if (!$in($sel(".osai-dialogbox__present"))) $html($sel("body"), "beforeend", `\n\t\t\t\t<div class="osai-dialogbox"><span style="display: none" class="osai-dialogbox__present"></span><div class="osai-dialogbox__overlay"></div><div class="osai-dialogbox__wrapper">\n                    <div class="osai-dialogbox__header"><button class="osai-dialogbox__close-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect><rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect></svg></button></div>\n                    <div class="osai-dialogbox__head"></div>\n                    <div class="osai-dialogbox__inner-wrapper"><div class="osai-dialogbox__body"></div></div>\n                    <div class="osai-dialogbox__foot"></div>\n                </div></div>`);
        if (!$in($sel(".osai-dialogbox__stylesheet"))) $html($sel("head"), "beforeend", `<style class="osai-dialogbox__stylesheet" rel="stylesheet" media="all">\n.osai-dialogbox{\nposition: fixed;\nright: 0; left: 0; top: 0; bottom: 0;\ndisplay: block;\nvisibility: hidden;\nopacity: 0;\nz-index: -${dialogZindex};\n}\n.osai-dialogbox__appear{\n\tvisibility: visible;\n\tz-index: ${dialogZindex};\n\topacity: 1;\n}\n.osai-dialogbox__overlay{\n\topacity: .5;\n\tposition: fixed;\n\ttop: 0;bottom: 0;left: 0;right: 0;\n\tbackground: var(--bg);\n\tz-index: 1;\n}\n.osai-dialogbox__wrapper{\n\tdisplay: flex;\n\topacity: 0;\n\tjustify-content: center;\n\talign-items: center;\n\tmax-width: 97vw;\n\tmax-height: 97vh;\n\ttransform: translate(-50%,0);\n\ttop: 50%; left: 50%;\n\tposition: absolute;\n\tz-index: 2;\n\tmargin: auto;\n    background: var(--dark-text);\n\tcolor: var(--bg);\n\tborder-radius: 10px;\n\tflex-flow: column;\n\ttransition: ease-in-out .8s all;\n\tpadding: 1.5rem;\n\tpadding-top: 0;\n\toverflow: hidden;\n}\n.osai-dialogbox__header{\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n\twidth: 100%;\n\tpadding: 0;\n\tpadding-top: 1.5rem;\n}\n.osai-dialogbox__close-btn{\n\tbackground: transparent;\n\tborder: none;\n\tcolor: var(--dark-info);\n\tfont-weight: 500;\n\tcursor: pointer;\n\toutline: none;\n\tmargin-left: auto;\n    position: relative;\n    z-index: 5;\n}\n.osai-dialogbox__close-btn:hover{\n\tcolor: var(--fail);\n}\n.osai-dialogbox__head{\n\tfont-size: 1.15rem;\n\tline-height: 1.15rem;\n\tpadding: 0;\n\tmargin-bottom: 1rem;\n\tfont-weight: 600;\n\twidth: 100%;\n}\n.osai-dialogbox__inner-wrapper{\n\toverflow: auto;\n\tmax-width: 100vw;\n\tpadding: 1.75rem 0;\n}\n.osai-dialogbox__body{\n\tfont-size: 1rem;\n}\n.osai-dialogbox__foot{\n    padding: 0;\n}\n.osai-dialogbox__foot button.success{\n\tbackground: var(--success);\n\tcolor: var(--bg);\n} .osai-dialogbox__foot button.success:hover{\n\tbackground: var(--dark-success);\n\tcolor: var(--dark-text);}\n.osai-dialogbox__foot button.fail{\n\tbackground: var(--fail);\n\tcolor: var(--text);\n}.osai-dialogbox__foot button.fail:hover{\n\tbackground: var(--dark-fail);\n\tcolor: var(--text);}\n.osai-dialogbox__foot button.warn{\n\tbackground: var(--warn);\n\tcolor: var(--text);\n} .osai-dialogbox__foot button.warn:hover{\n\tbackground: var(--dark-warn);\n\tcolor: var(--text);}\n.osai-dialogbox__foot button.info{\n\tbackground: var(--info);\n\tcolor: var(--dark-text);\n} .osai-dialogbox__foot button.info:hover{\n\tbackground: var(--dark-info);\n\tcolor: var(--text);}\n.osai-dialogbox__foot button.link{\n\tbackground: var(--link);\n\tcolor: var(--dark-text);\n} .osai-dialogbox__foot button.link:hover{\n\tbackground: var(--dark-link);\n\tcolor: var(--text);}\n\t.osai-dialogbox__foot button.success i,.osai-dialogbox__foot button.fail i, .osai-dialogbox__foot button.warn i, .osai-dialogbox__foot button.info i,.osai-dialogbox__foot button.link i{\n    color: var(--dark-text)\n}\n/* disable scrolling when modal is opened */\n.osai-modal__open{\n\toverflow-y: hidden;\n\tscroll-behavior: smooth;\n}\n.osai-modal__appear{\n\topacity: 1;\n\ttransform: translate(-50%,-50%);\n}\n.osai-modal__btn{\n\tborder-radius: .755rem;\n\tborder: solid 1px transparent;\n\tpadding: 0.65rem 1.73rem;\n\tcursor: pointer;\n\toutline: none;\n\ttransition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;\n\tbackground-color: var(--bg);\n\tcolor: var(--text);\n\tdisplay: inline-flex;\n\tjustify-content: center;\n\talign-items: center;\n}\n@media screen and (max-width: 600px){\n\t.osai-dialogbox__wrapper{\n\t\tmin-width: 90vw;\n\t\tmax-width: 95vw;\n\t\tmax-height: 90vh;\n\t}\n}\n</style>`);
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
            if (configSelector("header")) $style(BOX_HEADER, $data(configSelector("header"), "value"));
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
            if ($html(BOX_HEAD).trim() === "") $style(BOX_HEAD, "display:none");
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
                $style(BOX_HEADER, "del");
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
                header: BOX_HEADER,
                foot: BOX_FOOT,
                wrapper: BOX_INNER_WRAPPER,
                wrap: BOX_WRAPPER,
                body: BOX_BODY
            },
            config: ({align: align, size: size, closeOnBlur: closeOnBlur, wrapper: wrapper, head: head, header: header, foot: foot, body: body, close: close, zIndex: zIndex}) => {
                let addConfig = (config, value) => {
                    let element = config => $sel("input[data-config='" + config + "'].osai-dialogbox__config");
                    if (!element(config)) $html(BOX_PRESENCE, "beforeend", `<input type="hidden" class="osai-dialogbox__config" data-config="${config}" data-value="${value}">`); else $data(element(config), "value", value);
                };
                if (align) addConfig("box-body-align", align);
                if (size) addConfig("box-size", size);
                if (wrapper) addConfig("main-wrapper", wrapper);
                if (head) addConfig("head", head);
                if (header) addConfig("header", header);
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