var pdpa_popup = document.getElementsByClassName('dpdpa--popup');

var pdpda_popup_reject_all = document.getElementById('dpdpa--popup-reject-all');
var pdpa_popup_allow_all = document.getElementById('dpdpa--popup-accept-all');
var pdpa_popup_settings = document.getElementsByClassName('dpdpa--popup-settings');
var pdpa_popup_button_settings = document.getElementById('dpdpa--popup-button-settings');

var pdpa_sidebar = document.getElementsByClassName('dpdpa--popup-sidebar');
var pdpa_bg = document.getElementsByClassName('dpdpa--popup-bg');

var pdpa_close = document.getElementById('dpdpa--popup-close');
var pdpa_settings_close = document.getElementById('dpdpa--popup-settings-close');

var dpdpa_consent = document.getElementsByName('dpdpa_consent[]');
var dpdpa_consent_length = dpdpa_consent.length;

var dpdpa_consent_wrapper = document.getElementById('dpdpa--popup-list');
var pdpa_allow_all = document.getElementById('pdpa_settings_allow_all');
var pdpa_confirm = document.getElementById('pdpa_settings_confirm');

var dateStamp;


// -- Cookies -- //
var dpdpa_cookies = Cookies.get('dpdpa_consent');
var dpdpa_consent_close = Cookies.get('dpdpa_consent_close');
// -- Cookies -- //

// Document ready
document.addEventListener("DOMContentLoaded", function(event) {     
    init();    
    loadCode();
    // openPopupSettings();
});

// AJAX
var ajax = new XMLHttpRequest();
ajax.open('POST', pdpa_thailand.url, true);
ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

// ajax.onload = function () {

//     if (this.status >= 200 && this.status < 400) {
//         // var res = JSON.parse(ajax.response);        
//     } else {
//         console.log('Error');
//     }
// };

function init()
{
    // console.log(dpdpa_cookies);
    var cookieDaydiff = parseInt(pdpa_thailand.duration) + 1;
    var closeDaydiff = parseInt(pdpa_thailand.duration) + 1;
    var now = Math.floor((new Date).getTime()/1000);
    var daydiff = 0;
    var unique_id_chk = 0;

    // Set cookie to checkbox
    if ( dpdpa_cookies !== undefined )
    {   
        // console.log(dpdpa_cookies);     
        dpdpa_cookies = JSON.parse(dpdpa_cookies);                
        if ( dpdpa_cookies != '' && dpdpa_cookies !== undefined )
        {
            pdpa_choices = dpdpa_cookies.choices.split(',');            
                    
            for ( i = 0; i < dpdpa_consent_length; i++ )
            {   
                if ( pdpa_choices.includes(dpdpa_consent[i].value) )
                {
                    dpdpa_consent[i].checked = true;
                } else {
                    dpdpa_consent[i].checked = false;
                }

            }
        }            
        
        // Check date expired in cookie
        var dateStamp = dpdpa_cookies.datestamp;
        // var dateStamp = 1606819330;
        var datediff = now - dateStamp;
        cookieDaydiff = Math.round(datediff / (60 * 60 * 24));

        // console.log(pdpa_thailand.unique_id);
        // console.log(dpdpa_cookies.uniqueID);
        // Check uniqueID has been reset or not
        if ( dpdpa_cookies.uniqueID != pdpa_thailand.unique_id ) {
            unique_id_chk = 1;
        }
    }
    
    if ( ( cookieDaydiff > pdpa_thailand.duration || unique_id_chk == 1 ) && pdpa_thailand.enable == 1 )
    {
        setTimeout(function(){
            pdpa_popup[0].classList.add('active');
        }, 500);                
    }

    toggleAllowAll();    
}

// Allow all - Button
pdpa_popup_allow_all.addEventListener('click', function(event)
{
    forceAllowAll();            
    event.preventDefault();
});

// Settings - Allow all
pdpa_settings_allow_all.addEventListener('click', function(event)
{
    forceAllowAll();
    event.preventDefault();
});

// Reject All - Button
if ( pdpda_popup_reject_all !== null ) 
{
    pdpda_popup_reject_all.addEventListener('click', function(event)
    {
        rejectAll();
    });
}

// Close popup
pdpa_close.addEventListener('click', function(event)
{
    rejectAll();
});

// Close - Settings sidebar
pdpa_settings_close.addEventListener('click', function(event)
{
    closePopupSettings();       
    event.preventDefault();
});


// Close - settings sidebar
pdpa_bg[0].addEventListener('click', function(event)
{
    closePopupSettings();       
    event.preventDefault();
});


// Open - settings sidebar
if ( pdpa_popup_settings !== null ) 
{
    for(var i = 0; i < pdpa_popup_settings.length; i++) {
        (function(index) {
            pdpa_popup_settings[index].addEventListener("click", function(event) {
                openPopupSettings();
                event.preventDefault();
           })
        })(i);
    }
}

if ( pdpa_popup_button_settings !== null )
{
    pdpa_popup_button_settings.addEventListener('click', function(event)
    {    
        openPopupSettings();
        event.preventDefault();
    });
}

// Confirm - cookie
pdpa_confirm.addEventListener('click', function(event)
{   
    // Prepare cookies
    var cookies = prepareCookies();

    // Save cookies
    saveCookies(cookies);
    closePopupSettings();
    closePopup();
    
    event.preventDefault();
});

// Settings - Consent Checkbox
dpdpa_consent_wrapper.addEventListener('change', function(event)
{
    toggleAllowAll(); 
});

function loadCode()
{
    var res = callCookieList();    
    var code = {'code_in_head': '', 'code_next_body': '', 'code_body_close': ''};        
    var dpdpa_cookies = Cookies.get('dpdpa_consent');           

    // Position
    if ( dpdpa_cookies != '' && dpdpa_cookies !== undefined ) {                     
        dpdpa_cookies = JSON.parse(dpdpa_cookies);
        pdpa_choices = dpdpa_cookies.choices.split(',');            
        // console.log(pdpa_choices);
        // console.log(dpdpa_cookies);        

        for ( var key in res ) {
            
            // Code posiiton
            var obj = res[key];                
            var tagScript = '';
            // console.log('---' + key + '---');
            // console.log(obj);
                            
            for ( var ikey in obj ) {
                
                // This code
                var thisCode = obj[ikey];                                       
                // console.log(pdpa_choices);
                // console.log(pdpa_choices.includes(ikey));
                if ( pdpa_choices.includes(ikey) && thisCode != '' ) {
                    // // console.log('---' + ikey + '---');                                     
                    // console.log(tagString);
                    tagScript += thisCode + '\n';                                                
                }                    
            }
            // After concat code put in this array
            code[key] = tagScript.replace(/\\/g, "");
        }
    }    

    // Head
    var tagString = code.code_in_head;
    var range = document.createRange();
    range.selectNode(document.getElementsByTagName("head")[0]);
    var documentFragment = range.createContextualFragment(tagString);
    document.head.appendChild(documentFragment);
}

// Save Cookies
function saveCookies(cookies)
{       
    Cookies.set('dpdpa_consent', JSON.stringify(cookies));
    if ( !document.body.classList.contains('pdpa-thailand-loaded') ) 
    {
        document.body.classList.add("pdpa-thailand-loaded");
        loadCode();
        // ajax.send('action=pdpa_load_script&nonce=' + pdpa_thailand.nonce);
    } else {
        location.reload();
    }
        
}

// Prepare Cookies
function prepareCookies(cookies)
{
    dpdpa_cookies = [];

    for ( i = 0; i < dpdpa_consent_length; i++ )
    {
        if ( dpdpa_consent[i].checked == true )
        {
            dpdpa_cookies.push(dpdpa_consent[i].value);
        }
    }

    // console.log(dpdpa_cookies);
    
    // Time stamp
    dateStamp = Math.floor((new Date).getTime()/1000);
    uniqueID = pdpa_thailand.unique_id;

    return {uniqueID: uniqueID, datestamp: dateStamp, choices: dpdpa_cookies.join(',')};
}

// Reject All
function rejectAll()
{
    closePopup();

    for ( i = 0; i < dpdpa_consent_length; i++ )
    {
        dpdpa_consent[i].checked = false;
    }
    
    // Prepare cookies
    var cookies = prepareCookies();

    // Save cookies
    saveCookies(cookies);

    event.preventDefault();
}

// Close Popup
function closePopup()
{
    pdpa_popup[0].classList.remove('active');
}

// Close Settings
function closePopupSettings()
{
    pdpa_sidebar[0].classList.remove('active');
    pdpa_bg[0].classList.remove('active');
}

// Open Settings
function openPopupSettings()
{    
    pdpa_sidebar[0].classList.add('active');
    pdpa_bg[0].classList.add('active');
}

function forceAllowAll()
{
    for ( i = 0; i < dpdpa_consent_length; i++ )
    {
        dpdpa_consent[i].checked = true;
    }

    // Prepare cookies
    var cookies = prepareCookies();

    // Save cookies
    saveCookies(cookies);        
    
    closePopupSettings();
    closePopup();
}

// Settings - Toggle allow all button
function toggleAllowAll()
{
    var result = checkAllowAll();  
         
    
    if ( result == true ) {
        pdpa_allow_all.style.display = 'inline-block';
    } else {
        pdpa_allow_all.style.display = 'none';
    }
}

// Settings - Check allow all
function checkAllowAll() 
{
    var result = false;

    for ( i = 0; i < dpdpa_consent_length; i++ )
    {
        if ( dpdpa_consent[i].checked == false )
        {
            result = true;
            break;
        }
    }

    return result;
}