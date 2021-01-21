(function ($, Drupal, drupalSettings) {

  $(document).ready(function (){

    /** Initiate variables to empty strings **/

    let industry = "";
    let companySize = "";
    let companyType = "";

    /** Industry drop down select options **/

    $('#inputIndustry').change(function (){
      industry = $('#inputIndustry :selected').val();
      //alert(industry);
    });

    /** Company Size drop down select options **/

    $('#inputCompanySize').change(function (){
      companySize = $('#inputCompanySize :selected').val();
      //alert(companySize);
    });

    /** Location drop down select options **/

    $('#inputCompanyType').change(function (){
      companyType = $('#inputCompanyType :selected').val();
      //alert(companyType);
    });

    /** Button to submit form **/

    $('#btn_create_page').on('click', function (){

    /** Get Overview input value and website input value from user **/

    let name = $('#txt_name').val();
    let user_url_link = $('#txt_grmds_public_url').val().toLowerCase();
    let website = $('#txt_website').val();

    //alert(overView + " " + website);

    /** Get Contact Info, Headquarters, and founded input value from user **/

    let headquarters = $('#txt_Headquarters').val();
    let founded = $('#datepicker').val();

    let tagline = $('#txt_Tagline').val();


    let upload_picture = $('#upload_picture')[0].files[0];


      let form_data = new FormData();

      form_data.append('name', name);
      form_data.append('user_url_link', user_url_link);
      form_data.append('website', website);
      form_data.append('industry', industry);
      form_data.append('companySize', companySize);
      form_data.append('companyType', companyType);

      form_data.append('headquarters', headquarters);
      form_data.append('founded', founded);
      form_data.append('upload_picture', upload_picture);
      form_data.append('tagline', tagline);


      if(name !== ""){
        if(user_url_link !== ""){
          if(website !== ""){
            if(industry !== ""){
              if(companySize !== ""){
                if(companyType !== ""){
                      if($("#chk_box").prop("checked")) {

                        //alert("All information submitted!!!");

                        $.ajax({
                          url: "/insert/company_page",
                          type: "POST",
                          processData: false, // important
                          contentType: false, // important
                          data: form_data,
                          dataType: "JSON",
                          success: function (data){
                            console.log(data.url);

                            if(data.result === "success"){

                              window.location.href = "/company/" + data.url;

                            } else if(data.result === "error_url_exists"){

                              alert(data.msg);

                            } else if(data.result === "error_page_exists"){

                              alert(data.msg);

                              window.location.href = "/company/" + data.url;

                            } else {
                              alert(data.msg);
                            }

                          }

                        });

                      } else {
                        alert("You need to agree to our terms and conditions.");
                      }

                } else {
                  addClassToElements('#inputCompanyType');
                }
              } else {
                addClassToElements('#inputCompanySize');
              }
            } else {
              addClassToElements('#inputIndustry');
            }
          } else {
            addClassToElements('#txt_website');
          }
        } else {
          addClassToElements('#txt_grmds_public_url');
        }
      } else {
        addClassToElements('#txt_name');
      }

    });

    /** Add each keypress changes and blur elements **/

    keyPressElements('#txt_name');
    keyPressElements('#txt_grmds_public_url');
    keyPressElements('#txt_website');

    onBlurFocusSelectInputElements('#inputIndustry');
    onBlurFocusSelectInputElements('#inputCompanySize');
    onBlurFocusSelectInputElements('#inputCompanyType');

    /*keyPressElements('#txt_ContactInfo');
    keyPressElements('#txt_Headquarters');
    keyPressElements('#txt_Founded');*/



    onBlurElements('#txt_name');
    onBlurElements('#txt_grmds_public_url');
    onBlurElements('#txt_website');

    /*onBlurElements('#inputIndustry');
    onBlurElements('#inputCompanySize');
    onBlurElements('#inputLocation');*/

    /*onBlurElements('#txt_ContactInfo');
    onBlurElements('#txt_Headquarters');
    onBlurElements('#txt_Founded');*/


  });

  /** Create a function to handle input keypress changes for all elements **/
  function keyPressElements(targetElement){


    $(targetElement).keyup(function (){

      /** Count the number of characters in the input field **/
      let count = $(targetElement).keydown().val().length;
      //alert(count);

      /** Check if the count is at least 1 character in the input field **/
      if(count > 0){
        $(targetElement).removeClass('invalid');
      } else {
        $(targetElement).addClass('invalid');
      }

    });

  }

/** Create a function to handle all input blur elements **/
  function onBlurElements(targetElement){

    $(targetElement).blur(function (){
      //alert($(targetElement).val());
      if($(targetElement).val() === ""){
        $(targetElement).addClass('invalid');
      } else {
        $(targetElement).removeClass('invalid');
      }

    });

  }

  /** Create a function to handle select input elements blur and focus **/
  function onBlurFocusSelectInputElements(targetSelectInputElement){

    $(targetSelectInputElement).blur(function (){
      //alert($(targetElement).val());
      if($(targetSelectInputElement).val() === "" || $(targetSelectInputElement).val() === "" || $(targetSelectInputElement).val() === ""){
        $(targetSelectInputElement).addClass('invalid');
      }

    });

    $(targetSelectInputElement).on('change', function (){
      if($(targetSelectInputElement).val() !== "" || $(targetSelectInputElement).val() !== "" || $(targetSelectInputElement).val() !== ""){
        //alert($(targetSelectInputElement).val());
        $(targetSelectInputElement).removeClass('invalid');

      }

    });
  }


  function addClassToElements(addClassElement){
    $(addClassElement).addClass('invalid');
  }

  /** Initiate variables to empty strings **/

  let industry = "";
  let companySize = "";
  let companyType = "";

  let input_industry_value = document.getElementById('industry_value');
  let input_companySize_value = document.getElementById('company_size_value');
  let input_companyType_value = document.getElementById('company_type_value');
  /** Industry drop down select options **/

  $('#inputIndustry').change(function (){
    industry = $('#inputIndustry :selected').val();
    input_industry_value.value = industry;
  });



  /** Company Size drop down select options **/

  $('#inputCompanySize').change(function (){
    companySize = $('#inputCompanySize :selected').val();
    input_companySize_value.value = companySize;
    //alert(companySize);
  });

  /** Location drop down select options **/

  $('#inputCompanyType').change(function (){
    companyType = $('#inputCompanyType :selected').val();
    input_companyType_value.value = companyType
    //alert(companyType);
  });

  $('#btn_save_draft').on('click', function (){
    /*console.log(input_industry_value.value);
    alert(input_industry_value.value);*/

    let textbox_industry_value = input_industry_value.value;
    let textbox_companySize_value = input_companySize_value.value;
    let textbox_companyType_value = input_companyType_value.value;

    /** Get Overview input value and website input value from user **/

    let name = $('#txt_name').val();
    let user_url_link = $('#txt_grmds_public_url').val().toLowerCase();
    let website = $('#txt_website').val();

    //alert(overView + " " + website);

    /** Get Contact Info, Headquarters, and founded input value from user **/

    let headquarters = $('#txt_Headquarters').val();
    let founded = $('#txt_Founded').val();

    let upload_picture = $('#upload_picture').val();
    let tagline = $('#txt_Tagline').val();

    $.ajax({
      url: "/save_draft/company_page",
      type: "POST",
      data: {
        name: name,
        user_url_link: user_url_link,
        website: website,
        industry: textbox_industry_value,
        companySize: textbox_companySize_value,
        companyType: textbox_companyType_value,
        headquarters: headquarters,
        founded: founded,
        upload_picture: upload_picture,
        tagline: tagline,
      },

      dataType: "JSON",
      success: function (data){
        alert(data);

      }

    });

  });

})(jQuery, Drupal, drupalSettings);


