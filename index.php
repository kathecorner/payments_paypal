<?php

// 1. prepare api request to adyen library
// 2. get all payment methods for this shopper
// SGD, SG

$url = "https://checkout-test.adyen.com/v69/paymentMethods";

$payload = array(
  "merchantAccount" => "KenjiW",
  "countryCode" => "US",
  "channel" => "web",
  "amount" => [
    "value" => 1000,
    "currency" => "USD",
    ],
    "shopperReference" => "Shopper_02222022_1" //enable it when need to show tokanization
);

$curl_http_header = array(
   "X-API-Key: AQEyhmfxL4PJahZCw0m/n3Q5qf3VaY9UCJ1+XWZe9W27jmlZiv4PD4jhfNMofnLr2K5i8/0QwV1bDb7kfNy1WIxIIkxgBw==-lUKXT9IQ5GZ6d6RH4nnuOG4Bu//eJZxvoAOknIIddv4=-<anpTLkW{]ZgGy,7",
   "Content-Type: application/json"
);

$curl = curl_init();

curl_setopt_array(
    $curl,
    [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $curl_http_header,
        CURLOPT_VERBOSE        => true
    ]
);

$paymentmethodsrequestresponse = json_encode(curl_exec($curl));

curl_close($curl);

//var_dump($paymentmethodsrequestresponse);

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
    <link rel="stylesheet"
     href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.7.0/adyen.css"
     integrity="sha384-dkJjySvUD62j8VuK62Z0VF1uIsoa+APxWLDHpTjBRwQ95VxNl7oaUvCL+5WXG8lh"
     crossorigin="anonymous">

     <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.7.0/adyen.js"
     integrity="sha384-Hmnh/5ShP0Q8iCjGV2U/6XFi7jiiFys4fsh7UrCH1JT1PV1ThZ9czMnbbyjzxuhU"
     crossorigin="anonymous"></script>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js" charset="utf-8"></script>
  </head>
  <body>
    <h1>PayPal simple sample</h1>

    <div id="kenjis-dropin"></div>

    <script type="text/javascript">

      var availablePaymentMethods = JSON.parse( <?php echo $paymentmethodsrequestresponse; ?> );

      function makePayment(state) {
          const prom_data = state;
          return new Promise(
              function (resolve,reject) {
                  $.ajax(
                      {
                          type: "POST",
                          url: "/processpayment.php",
                          data: prom_data,
                          success: function (response) {
                              resolve(response);
                          }
                      }
                  );
              }
          );

      }

      function showFinalResult(data){
          //console.log(JSON.parse(data.resultCode));
          //var responseData = JSON.parse(data);
          var responseData = data;

          if(responseData.resultCode == "Authorised"){
              alert('PAYMENT SUCCESSFUL!');
              //window.location.href = 'http://127.0.0.1:8080/return.php';
              window.location.href = 'http://127.0.0.1:8080/showResults.php';
          }
      }

      function show3DSResult(data){
        if(data.resultCode == "Authorised"){

            alert(data.resultCode);

            var response_list = data;
            var response_list_all;

            for (var i=0; i<response_list.length;i++){
              response_list_all += '<li>' + response_list[i] + '</li>';
            }
            //document.getElementById('response_list_all').innerHTML = response_list_all;
            document.write(data.resultCode);
        }else
        {
          //alert('PAYMENT UNSUCCESSFUL!');
          //window.location.href = 'http://127.0.0.1:8080/return.php';
          window.location.href = 'http://127.0.0.1:8080/showResults.php';

        }
        /*
        console.log("makeAdditionalDetails_2(data)");*/
      }

      function makeAdditionalDetails(state){
        //alert('makeAdditionalDetials');

        const detail_data = state;
        return new Promise(
          function (resolve,reject){
            $.ajax(
              {
                type: "POST",
                url: "additionaldetails.php",
                data: detail_data,
                success: function (response) {
                  resolve(response);
                  console.log(response);
                }
              }
            );
            }
            )
          }

      var configuration = {
        paymentMethodsResponse : availablePaymentMethods,
        clientKey: "test_RKKBP5GHOFFUFJJMJHOJAG7ZIIJKBMI6",
        locale: "en-US",
        showPayButton: true,
        environment: "test",
        hasHolderName: false,//added on Aug30
        holderNameRequired: false,//added on Aug30
        enableStoreDetails: false,//added on Aug30
        billingAddressRequired: false,//added on Aug30
        hideCVC: true,
        onSubmit: (state,dropin)=>{
            makePayment(state.data)
                .then(response => {
                    var responseData = response.action;
                    console.log(response);
                    if(response.action) {
                        dropin.handleAction(response.action);
                    }
                    else{
                        showFinalResult(response);
                    }
                })
                .catch(error => {
                    console.log(error);
                    throw Error(error);
                });
        },
        onAdditionalDetails: (state,dropin)=>{
          //alert('onAdditionalDetails called.');
          $a_params = state.data;
          makeAdditionalDetails(state.data)
            .then(response => {
              var responseDetail = response.action;
              console.log(response);
              if(response.action) {
                //alert('action received.');
                dropin.handleAction(response.action);
                //show3DSResult(response);
              }
              else{
                show3DSResult(response);
                console.log(response);
              }
            })
            .catch(error => {
              console.log(error);
              throw Error(error);
            });
        },
        paymentMethodsConfiguration: {
            card:{
                hasHolderName: true,
                holderNameRequired: true,
                enableStoreDetails: true,
                name: 'Credit or debit card',
                billingAddressRequired: false,
                hideCVC: true,
                //to show installment options
                installmentOptions: {
                  card: {
                    values: [1,2,3,5],
                    //plans: ['regular','revolving']
                    plans: ['regular']
                  },
                  showInstallmentAmounts: true
                }

            },
            threeDS2: {
              challengeWindowSize: '05'
            },
        }
      }


      const checkout = new AdyenCheckout(configuration);

      const dropin = checkout.create('paypal').mount('#kenjis-dropin');
      //const dropin = checkout.create('card').mount('#kenjis-dropin');
      //const storedPaymentMethod = checkout.paymentMethodsResponse.storedPaymentMethods[0]; 
      //const card = checkout.create("card", storedPaymentMethod).mount("#kenjis-dropin");


    </script>
  </body>
</html>
