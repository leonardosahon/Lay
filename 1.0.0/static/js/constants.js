const $lay = {};

$lay['page'] = {
    title : $id("LAY-PAGE-TITLE").content,
    title_full : $id("LAY-PAGE-TITLE").innerHTML,
    desc : $attr($id("LAY-PAGE-DESC"),"content"),
    type : $attr($id("LAY-PAGE-TYPE"),"content"),
    root : $attr($id("LAY-PAGE-ROOT"),"content"),
    url : $attr($id("LAY-PAGE-URL"),"content"),
    img : $attr($id("LAY-PAGE-IMG"),"content"),
    site_name : $attr($id("LAY-SITE-NAME-SHORT"),"content"),
    site_name_full : $attr($id("LAY-SITE-NAME"),"content"),
}
$lay["src"] = {
    base : $id("LAY-PAGE-BASE").href,
    api : $id('LAY-API').value + "?c=",
    custom_img : $id("LAY-CUSTOM-IMG").value,
    back_img : $id("LAY-BACK-IMG").value,
    front_img : $id("LAY-FRONT-IMG").value,
    uploads : $id("LAY-UPLOAD").value,
}
$lay["fn"] = {
    copy: (str, successMsg = "Link copied successfully") => {
        if(navigator.clipboard) {
            navigator.clipboard.writeText(str)
            osNote(successMsg,"success")
            return true
        }

        const el = document.createElement('textarea');
        el.value = str;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        osNote(successMsg,"success")
        return true
    },
    rowEntrySave: row => `<span style="display: none" class="d-none entry-row-info">${JSON.stringify(row).replace(/&quot;/g,'\\"')}</span>`,
    /**
     * Activate the action buttons on a table automatically using the `table-actions` class
     * @param actionsObject
     * @example [...].tableAction({delete: ({id,name}) => [id,name,...]})
     */
    rowEntryAction: (btn) => {
        $loop(actionsObject, (value, key) => {
            // the data-action value must be same with the key of the action being passed into the script
            if($data(btn,"action") === key) {
                let parentElement = btn.closest(".table-actions-parent") ?? btn.closest("td")

                value({
                    id: $data(btn, "id"),
                    name: decodeURIComponent($data(btn, "name")),
                    item: btn,
                    params: $data(btn, "params")?.split(","),
                    info: !$sel(".entry-row-info", parentElement) ? "" : JSON.parse($html($sel(".entry-row-info", parentElement)))
                })
            }
        })
    },
    currency : (num,currency = "USD",locale = "en-US") => {
        return new Intl.NumberFormat(locale,{
            style: "currency",
            currency: currency,
        }).format(num)
    },
}