// Initialize the application
EcwidApp.init({
  app_id: "quickpay-payments-mi", // use your application namespace
  autoloadedflag: true, 
  autoheight: true
});

var storeData = EcwidApp.getPayload();

var storeId = storeData.store_id;
var accessToken = storeData.access_token;
var language = storeData.lang;
var viewMode = storeData.view_mode;

if (storeData.public_token !== undefined){
  var publicToken = storeData.public_token;
}

if (storeData.app_state !== undefined){
  var appState = storeData.app_state;
}


