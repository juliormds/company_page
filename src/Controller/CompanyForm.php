<?php

namespace Drupal\company_page\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Element\Ajax;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CompanyForm extends ControllerBase{

  public function getCacheMaxAge() {
    return 0;
  }

  public function displayForm(){

    /** get database connection and current user id **/
    $db = Database::getConnection();
    $uid = \Drupal::currentUser()->id();

    /** Check if there is a draft from user to display from draft table **/

    $query = $db->query("SELECT * FROM {dr_save_draft_company_page} WHERE uid = :uid", [':uid' => $uid]);
    $result = $query->fetchAll();

    if(count($result) === 1){

      /** Pull current user information from database **/

      return [
        '#theme' => 'company_form',
        '#name_of_user' => $result[0]->name_of_user,
        '#grmds_url' => $result[0]->grmds_url,
        '#website' => $result[0]->website,
        '#industry' => $result[0]->industry,
        '#company_size' => $result[0]->company_size,
        '#company_type' => $result[0]->company_type,
        '#headquarters' => $result[0]->headquarters,
        '#founded' => $result[0]->founded,
        '#page_picture' => $result[0]->page_picture,
        '#tagline' => $result[0]->tagline,
      ];

    } else {
      return [
        '#theme' => 'company_form',
      ];
    }

  }

  public function insertData(){

    /** Get form values inputted by user from ajax **/

    $name = $_POST['name'];
    $user_url_link = $_POST['user_url_link'];
    $website = $_POST['website'];
    $industry = $_POST['industry'];
    $companySize = $_POST['companySize'];
    $companyType = $_POST['companyType'];
    $headquarters = $_POST['headquarters'];
    $founded = $_POST['founded'];
    $tagline = $_POST['tagline'];

    $valid_extensions = array('jpeg', 'jpg', 'png'); // valid extensions

    $upload_picture_name = $_FILES['upload_picture']['name'];
    $upload_picture_size = $_FILES['upload_picture']['size'];
    $upload_picture_temp = $_FILES['upload_picture']['tmp_name'];


    /** Convert founded = $_POST['founded']; to MySQL appropriate date/time value **/

    //$time_founded = \Drupal::service('date.formatter')->format($founded, 'custom', 'Y/m/d');

    /** get database connection and current user id **/
    $db = Database::getConnection();
    $uid = \Drupal::currentUser()->id();
    //$name = \Drupal::currentUser()->getAccountName();

    /** check if user is not in database **/
    $query = $db->query("SELECT * FROM {dr_user_company_page} WHERE uid = :uid", [':uid' => $uid]);
    $result = $query->fetchAll();

    if(count($result) === 0){

      /** Check is GRMDS url is not taken **/
      $query_check_grmds_url = $db->query("SELECT * FROM {dr_user_company_page} WHERE grmds_url = :url", [':url' => $user_url_link]);
      $result_check_grmds_url = $query_check_grmds_url->fetchAll();

      /** Check the results **/
      if(count($result_check_grmds_url) === 0) {
        if(isset($upload_picture_name)) {

          $upload_destination = 'public://inline-images/company_images/';

          $ext = strtolower(pathinfo($upload_picture_name, PATHINFO_EXTENSION));

          // can upload same image using rand function
          $final_image = rand(1000, 1000000) . $upload_picture_name;
          $upload_destination = $upload_destination . strtolower($final_image);

          if(in_array($ext, $valid_extensions)) {

            if($upload_picture_size > 0 && $upload_picture_size <= 3000000) {

              if(move_uploaded_file($upload_picture_temp, $upload_destination)) {

                /** Delete any drafts from user in the save draft company page table **/

                $db->delete('dr_save_draft_company_page')
                  ->condition('uid', $uid)
                  ->execute();

                /** insert user data to the database **/

                $db->insert('dr_user_company_page')
                  ->fields([
                    'uid' => $uid,
                    'name_of_user' => $name,
                    'grmds_url' => $user_url_link,
                    'website' => $website,
                    'industry' => $industry,
                    'company_size' => $companySize,
                    'company_type' => $companyType,
                    'headquarters' => $headquarters,
                    'founded' => $founded,
                    'page_picture' => $final_image,
                    'tagline' => $tagline,
                  ])
                  ->execute();

                return new AjaxResponse(['result' => 'success', 'url' => $user_url_link]);

              } else {
                return new AjaxResponse(['result' => 'error', 'msg' => 'File upload failed']);
              }
            }else {
              return new AjaxResponse(['result' => 'error', 'msg' => 'Images must be a minimum of 3MB.']);
            }
          }else {
            return new AjaxResponse(['result' => 'error', 'msg' => 'Not a valid image file.']);
          }
        }else {
          /** Delete any drafts from user in the save draft company page table **/

          $db->delete('dr_save_draft_company_page')
            ->condition('uid', $uid)
            ->execute();

          /** insert user data to the database **/

          $db->insert('dr_user_company_page')
            ->fields([
              'uid' => $uid,
              'name_of_user' => $name,
              'grmds_url' => $user_url_link,
              'website' => $website,
              'industry' => $industry,
              'company_size' => $companySize,
              'company_type' => $companyType,
              'headquarters' => $headquarters,
              'founded' => $founded,
              'page_picture' => NULL,
              'tagline' => $tagline,
            ])
            ->execute();

          return new AjaxResponse(['result' => 'success', 'url' => $user_url_link]);
        }
      } else {
        return new AjaxResponse(['result' => 'error_url_exists', 'msg' => 'URl link already exists, please choose another one.']);
      }

    } else {
      return new AjaxResponse(['result' => 'error_page_exists', 'msg' => 'You already created a page', 'url' => $result[0]->grmds_url]);
    }


    //return new AjaxResponse("This is coming from the php controller". " industry: ". $industry. " - ". " companySize: ". $companySize. " - ". " location: ". $location. " - ");
  }

  /** Display user's page based on GRMDS URL **/

  public function displayUserPage($url){

    $db = Database::getConnection();
    $logged_in_user_in = \Drupal::currentUser()->id();


    $query = $db->query("SELECT * FROM {dr_user_company_page} WHERE grmds_url = :url", [':url' => $url]);
    $result = $query->fetchAll();

    $followee_id = $result[0]->uid;

    if(count($result) === 1){

      $query_follow = $db->query("SELECT * FROM {dr_user_follow_page} WHERE followee_id = :followee_id", [':followee_id' => $followee_id]);
      $result_follow = $query_follow->fetchAll();

      $match_following_follower_query = $db->query
      ("SELECT * FROM {dr_user_follow_page} WHERE (follower_id = :current_loggedin_user AND followee_id = :followee_id)

       ", [':current_loggedin_user' => $logged_in_user_in, ':followee_id' => $followee_id]);
      $match_result = $match_following_follower_query->fetchAll();

      /** Check if there are any posts for the user **/

      $posts_query = $db->query("SELECT n.id, n.poster_id, n.post_message, n.post_image,
                                              n.created_at, o.name_of_user,
                                              o.page_picture, o.grmds_url
                                       FROM {dr_user_posts} n
                                       INNER JOIN {dr_user_company_page} o
                                       ON o.uid = n.poster_id
                                       WHERE n.poster_id = :uid
                                       ORDER BY created_at DESC",
                                       [':uid' => $followee_id]);
      $posts_result = $posts_query->fetchAll();

      return [
        '#theme' => 'user_page',
        '#logged_in_user_id' => $logged_in_user_in,
        '#uid' => $result[0]->uid,
        '#name_of_user' => $result[0]->name_of_user,
        '#grmds_url' => $result[0]->grmds_url,
        '#website' => $result[0]->website,
        '#industry' => $result[0]->industry,
        '#company_size' => $result[0]->company_size,
        '#company_type' => $result[0]->company_type,
        '#headquarters' => $result[0]->headquarters,
        '#founded' => $result[0]->founded,
        '#page_picture' => $result[0]->page_picture,
        '#tagline' => $result[0]->tagline,
        '#about' => $result[0]->about,
        '#postresultarray' => $result,
        '#count_following' => count($result_follow),
        '#count_following_each_other' => count($match_result),
        '#post_id' => $posts_result[0]->id,
        '#poster_id' => $posts_result[0]->poster_id,
        '#poster_count' => count($posts_result),
        '#allresults' => $posts_result,
      ];

    } else {
      return [
        '#theme' => 'error_no_user_page',
      ];
    }

  }

  public function saveDraft(){

    $created_at = \Drupal::time()->getCurrentTime();
    $time = \Drupal::service('date.formatter')->format($created_at, 'custom', 'Y-m-d H:i:s');

    $db = Database::getConnection();
    $uid = \Drupal::currentUser()->id();

    /** Get variables from ajax call **/

    $name = $_POST['name'];
    $user_url_link = $_POST['user_url_link'];
    $website = $_POST['website'];
    $industry = $_POST['industry'];
    $companySize = $_POST['companySize'];
    $companyType = $_POST['companyType'];
    $headquarters = $_POST['headquarters'];
    $founded = $_POST['founded'];
    $upload_picture = $_POST['upload_picture'];
    $tagline = $_POST['tagline'];

    /*echo $industry;
    die();*/

    /** Create a select query and return count based on uid of user **/

    $exists = $db->select('dr_save_draft_company_page')
      ->condition('uid', $uid)
      ->countQuery()
      ->execute()->fetchField();

    /** Check if user is already in the database **/
    if($exists == 0) {

      $db->insert('dr_save_draft_company_page')
        ->fields([
          'uid' => $uid,
          'name_of_user' => $name,
          'grmds_url' => $user_url_link,
          'website' => $website,
          'industry' => $industry,
          'company_size' => $companySize,
          'company_type' => $companyType,
          'headquarters' => $headquarters,
          'founded' => $founded,
          'page_picture' => $upload_picture,
          'tagline' => $tagline,
        ])
        ->execute();

      return new AjaxResponse('Draft saved! From server!');

    } else {

      /** Update the current user saved draft information **/

      /*$db->query("UPDATE {dr_save_draft_company_page} SET ? WHERE uid = :uid", [':uid' => $uid])
         ->execute();*/

      $db->update('dr_save_draft_company_page')
        ->fields([
          'uid' => $uid,
          'name_of_user' => $name,
          'grmds_url' => $user_url_link,
          'website' => $website,
          'industry' => $industry,
          'company_size' => $companySize,
          'company_type' => $companyType,
          'headquarters' => $headquarters,
          'founded' => $founded,
          'page_picture' => $upload_picture,
          'tagline' => $tagline,
          'updated_at' => $time,
        ])
        ->condition('uid', $uid)
        ->execute();
      return new AjaxResponse('Draft Updated!');
    }


    //return new AjaxResponse('Draft saved from server side');

  }

  /** Edit company page  **/

  public function editCompanyPage($uid){

    $db = Database::getConnection();

    $query = $db->query("SELECT * FROM {dr_user_company_page} WHERE uid = :uid", [':uid' => $uid]);
    $result = $query->fetchAll();

    if(count($result) === 1){

      return [
        '#theme' => 'user_edit_page',
        '#uid' => $result[0]->uid,
        '#name_of_user' => $result[0]->name_of_user,
        '#grmds_url' => $result[0]->grmds_url,
        '#website' => $result[0]->website,
        '#industry' => $result[0]->industry,
        '#company_size' => $result[0]->company_size,
        '#company_type' => $result[0]->company_type,
        '#headquarters' => $result[0]->headquarters,
        '#founded' => $result[0]->founded,
        '#page_picture' => $result[0]->page_picture,
        '#tagline' => $result[0]->tagline,
      ];

    } else {
      return new AjaxResponse(['result' => 'error', 'msg' => 'No user found']);
    }

  }

  public function updateUserPage($uid){

    $created_at = \Drupal::time()->getCurrentTime();
    $time = \Drupal::service('date.formatter')->format($created_at, 'custom', 'Y-m-d H:i:s');

    $db = Database::getConnection();

    /** Get variables from ajax call **/

    $name = $_POST['name'];
    $user_url_link = $_POST['user_url_link'];
    $website = $_POST['website'];
    $industry = $_POST['industry'];
    $companySize = $_POST['companySize'];
    $companyType = $_POST['companyType'];
    $headquarters = $_POST['headquarters'];
    $founded = $_POST['founded'];
    $tagline = $_POST['tagline'];

    $valid_extensions = array('jpeg', 'jpg', 'png'); // valid extensions

    $upload_picture_name = $_FILES['upload_picture']['name'];
    $upload_picture_size = $_FILES['upload_picture']['size'];
    $upload_picture_temp = $_FILES['upload_picture']['tmp_name'];

    if(!isset($upload_picture_name)) {

      $update_query = $db->update('dr_user_company_page')
        ->fields([
          'uid' => $uid,
          'name_of_user' => $name,
          'grmds_url' => $user_url_link,
          'website' => $website,
          'industry' => $industry,
          'company_size' => $companySize,
          'company_type' => $companyType,
          'headquarters' => $headquarters,
          'founded' => $founded,
          'tagline' => $tagline,
          'updated_at' => $time,
        ])
        ->condition('uid', $uid);
      $result = $update_query->execute();


      $new_checkUpdateQuery = $db->query("SELECT * FROM {dr_user_company_page} WHERE uid = :uid", [':uid' => $uid]);
      $new_check_result = $new_checkUpdateQuery->fetchAll();

      $db_new_grmds_url = $new_check_result[0]->grmds_url;


      return new AjaxResponse(['result' => 'success', 'url' => $db_new_grmds_url]);

    } else {

      $upload_destination = 'public://inline-images/company_images/';

      $ext = strtolower(pathinfo($upload_picture_name, PATHINFO_EXTENSION));

      // can upload same image using rand function
      $final_image = rand(1000, 1000000) . $upload_picture_name;
      $upload_destination = $upload_destination . strtolower($final_image);

      if(in_array($ext,$valid_extensions)){

        if($upload_picture_size > 0 && $upload_picture_size <= 3000000){

          if(move_uploaded_file($upload_picture_temp, $upload_destination)){

            /** Pull the old image from the database to delete it **/
            $edit_query = $db->query("SELECT * FROM {dr_user_company_page} WHERE uid = :uid", [':uid' => $uid]);
            $edit_results = $edit_query->fetchAll();

            $db_post_image = $edit_results[0]->page_picture;

            /** Delete the old image to upload the new image. **/
            unlink('public://inline-images/company_images/'. $db_post_image);

            $update_query = $db->update('dr_user_company_page')
              ->fields([
                'page_picture' => $final_image,
              ])
              ->condition('uid', $uid);
            $result = $update_query->execute();

            return new AjaxResponse(['result' => 'success', 'url' => $edit_results[0]->grmds_url]);

          }else {
            return new AjaxResponse(['result' => 'error', 'msg' => 'Something went wrong with the file upload.']);
          }


        } else {
          return new AjaxResponse(['result' => 'error', 'msg' => 'The file is too big only up to 3MB allowed.']);
        }


      } else {
        return new AjaxResponse(['result' => 'error', 'msg' => 'Not a valid file type.']);
      }

    }

  }

  public function saveFollowingUserPage($followingID){

    /** Create database connection and grab current logged in user ID **/
    $db = Database::getConnection();
    $current_loggedin_user_ID = \Drupal::currentUser()->id();

    /** Insert followerID and followingID to database **/

    $insert_update = $db->upsert('dr_user_follow_page')
      ->fields([
        'follower_id' => $current_loggedin_user_ID,
        'followee_id' => $followingID,
      ]);
    $insert_update->key('follower_id');
    $result = $insert_update->execute();


   return new AjaxResponse($result);
  }

  public function createPosts(){

    /** Create database connection and grab current logged in user ID **/
    $db = Database::getConnection();
    $current_loggedin_user_ID = \Drupal::currentUser()->id();

    $time = time();

    /** Create php variables from ajax request **/
    $poster_company_message = $_POST['postMessage'];


    $valid_extensions = array('jpeg', 'jpg', 'png'); // valid extensions

    $poster_image = $_FILES['postImage']['name'];
    $poster_image_size = $_FILES['postImage']['size'];
    $poster_image_temp = $_FILES['postImage']['tmp_name'];

    if(isset($poster_image)) {

      $upload_destination = 'public://inline-images/posts/';

      $ext = strtolower(pathinfo($poster_image, PATHINFO_EXTENSION));

      // can upload same image using rand function
      $final_image = rand(1000, 1000000) . $poster_image;
      $upload_destination = $upload_destination. strtolower($final_image);

      if (in_array($ext, $valid_extensions)) {

        if ($poster_image_size > 0 && $poster_image_size <= 3000000) {
          if (move_uploaded_file($poster_image_temp, $upload_destination)) {

            $insert_update = $db->insert('dr_user_posts')
              ->fields([
                'poster_id' => $current_loggedin_user_ID,
                'post_image' => $final_image,
                'post_message' => $poster_company_message,
                'created_at' => $time,
              ]);

            $result = $insert_update->execute();

            /** Pull newly inserted data back to ajax **/

            $pull_query = $db->query("SELECT n.id, n.poster_id, n.post_message, n.post_image,
                                              n.created_at, o.name_of_user,
                                              o.page_picture, o.grmds_url
                                       FROM {dr_user_posts} n
                                       INNER JOIN {dr_user_company_page} o
                                       ON o.uid = n.poster_id
                                       WHERE n.poster_id = :uid
                                       ORDER BY created_at DESC",
              [':uid' => $current_loggedin_user_ID]);

            $pull_result = $pull_query->fetchAll();

            for($i = 0; $i < count($pull_result); $i++){

              $db_pull_id = $pull_result[$i]->id;
              $db_pull_poster_id = $pull_result[$i]->poster_id;
              $db_pull_post_image = $pull_result[$i]->post_image;
              $db_pull_post_message = $pull_result[$i]->post_message;
              $db_pull_created_at = $pull_result[$i]->created_at;

              $db_pull_name_of_user = $pull_result[$i]->name_of_user;
              $db_pull_page_picture = $pull_result[$i]->page_picture;

              return new AjaxResponse(
                [
                  "result" => "success",
                  "msg" => "Success!",
                  "id" => $db_pull_id,
                  "poster_id" => $db_pull_poster_id,
                  "post_image" => strtolower($db_pull_post_image),
                  "post_message" => $db_pull_post_message,
                  "name_of_user" => $db_pull_name_of_user,
                  "page_picture" => $db_pull_page_picture,
                  "created_at" => $db_pull_created_at
                ]);
            }

            return new AjaxResponse();

          } else {
            return new AjaxResponse(["result" => "error", "msg" => "Error uploading the file."]);
          }
        } else {
          return new AjaxResponse(["result" => "error", "msg" => "Image must be less than or equal to 1MB."]);
        }

      } else {
        return new AjaxResponse(["result" => "error", "msg" => "Invalid file type."]);
      }
    } else {
      $insert_update = $db->insert('dr_user_posts')
        ->fields([
          'poster_id' => $current_loggedin_user_ID,
          'post_image' => NULL,
          'post_message' => $poster_company_message,
          'created_at' => $time,
        ]);

      $result = $insert_update->execute();

      $pull_query = $db->query("SELECT n.id, n.poster_id, n.post_message, n.post_image,
                                              n.created_at, o.name_of_user,
                                              o.page_picture, o.grmds_url
                                       FROM {dr_user_posts} n
                                       INNER JOIN {dr_user_company_page} o
                                       ON o.uid = n.poster_id
                                       WHERE n.poster_id = :uid
                                       ORDER BY created_at DESC",
        [':uid' => $current_loggedin_user_ID]);
      $pull_result = $pull_query->fetchAll();

      for($i = 0; $i < count($pull_result); $i++){

        $db_pull_id = $pull_result[$i]->id;
        $db_pull_poster_id = $pull_result[$i]->poster_id;
        $db_pull_post_image = $pull_result[$i]->post_image;
        $db_pull_post_message = $pull_result[$i]->post_message;
        $db_pull_created_at = $pull_result[$i]->created_at;

        $db_pull_name_of_user = $pull_result[$i]->name_of_user;
        $db_pull_page_picture = $pull_result[$i]->page_picture;

        return new AjaxResponse(
          [
            "result" => "success",
            "msg" => "Success!",
            "id" => $db_pull_id,
            "poster_id" => $db_pull_poster_id,
            "post_image" => $db_pull_post_image,
            "post_message" => $db_pull_post_message,
            "name_of_user" => $db_pull_name_of_user,
            "page_picture" => $db_pull_page_picture,
            "created_at" => $db_pull_created_at
          ]);
      }
    return new AjaxResponse();

    }
  }

  /** Delete Posts **/

  public function deletePosts($id){
    $db = Database::getConnection();

    /** Pull the old image from the database to delete it **/
    $post_query = $db->query("SELECT * FROM {dr_user_posts} WHERE id = :id", [':id' => $id]);
    $post_results = $post_query->fetchAll();

    $db_post_image = $post_results[0]->post_image;

    /** Delete the old image **/
    unlink('public://inline-images/posts/'. $db_post_image);

    $db->delete('dr_user_posts')
      ->condition('id', $id)
      ->execute();

    return new AjaxResponse(['result' => 'success']);

  }

  /** Save user about section **/

  public function saveAbout($uid){

    $created_at = \Drupal::time()->getCurrentTime();
    $time = \Drupal::service('date.formatter')->format($created_at, 'custom', 'Y-m-d H:i:s');

    /** Connect to database **/
    $db = Database::getConnection();

    $about_text = $_POST['edit_txt_about'];


    $insert_update = $db->update('dr_user_company_page')
      ->fields([
        'about' => $about_text,
        'updated_at' => $time,
      ]);

    $insert_update->condition('uid', $uid);
    $insert_update->execute();

    return new AjaxResponse(["result" => "success", "msg" => 'Data saved!']);

  }

  public function edit_post($id){

    /** Connect to database **/
    $db = Database::getConnection();

    $edit_query = $db->query("SELECT * FROM {dr_user_posts} WHERE id = :id", [':id' => $id]);
    $edit_results = $edit_query->fetchAll();

    /** I'm appending this html content in the post-edit-modal.html.twig **/
    $modalHTML = ' <div class="form-group">
            <label for="edit_txt_post_message">Post Message</label>
            <textarea cols="30" class="form-control edit_txt_post_message" rows="10" placeholder="Post your message">'. $edit_results[0]->post_message .'</textarea>

          </div>';

      return new AjaxResponse(
        [
          'result' => 'success',
          'id' => $edit_results[0]->id,
          'modalHTML' => $modalHTML
        ]
      );

  }

  public function saveEditPost($id){

    /** Create database connection and grab current logged in user ID **/
    $db = Database::getConnection();

    $current_loggedin_user_ID = \Drupal::currentUser()->id();

    $time = time();

    /** Create php variables from ajax request **/
    $edit_message = $_POST['edit_message'];

    $valid_extensions = array('jpeg', 'jpg', 'png'); // valid extensions

    $poster_image = $_FILES['edit_post_image']['name'];
    $poster_image_size = $_FILES['edit_post_image']['size'];
    $poster_image_temp = $_FILES['edit_post_image']['tmp_name'];


    if(isset($poster_image)) {

      $upload_destination = 'public://inline-images/posts/';

      $ext = strtolower(pathinfo($poster_image, PATHINFO_EXTENSION));

      // can upload same image using rand function
      $final_image = rand(1000, 1000000) . $poster_image;
      $upload_destination = $upload_destination . strtolower($final_image);

      if (in_array($ext, $valid_extensions)) {

        if ($poster_image_size > 0 && $poster_image_size <= 3000000) {
          if (move_uploaded_file($poster_image_temp, $upload_destination)) {

            /** Pull the old image from the database to delete it **/
            $edit_query = $db->query("SELECT * FROM {dr_user_posts} WHERE id = :id", [':id' => $id]);
            $edit_results = $edit_query->fetchAll();

            $db_post_image = $edit_results[0]->post_image;
            $db_post_message = $edit_results[0]->post_message;

            /** Delete the old image to upload the new image. **/
            unlink('public://inline-images/posts/'. $db_post_image);

            /** Update new image and/or message if changed **/
            $update = $db->update('dr_user_posts')
              ->fields([
                'post_image' => $final_image,
                'post_message' => $edit_message,
                'created_at' => $time,
              ]);

            $update->condition('id', $id);
            $result = $update->execute();

            /** Pull the newly updated info from database **/
            $updated_post_query = $db->query("SELECT * FROM {dr_user_posts} WHERE id = :id", [':id' => $id]);
            $updated_post_result = $updated_post_query->fetchAll();

            $db_updated_post_message = $updated_post_result[0]->post_message;
            $db_updated_post_image = $updated_post_result[0]->post_image;
            $db_updated_created_at = $updated_post_result[0]->created_at;

            return new AjaxResponse(
              [
                "result" => "success",
                "msg" => "Success!",
                "post_message" => $db_updated_post_message,
                "post_image" => strtolower($db_updated_post_image),
                "created_at" => $db_updated_created_at
              ]);

          } else {
            return new AjaxResponse(["result" => "error", "msg" => "Error uploading the file."]);
          }
        } else {
          return new AjaxResponse(["result" => "error", "msg" => "Image must be less than or equal to 1MB."]);
        }

      } else {
        return new AjaxResponse(["result" => "error", "msg" => "Invalid file type."]);
      }

    }else {
      $update = $db->update('dr_user_posts')
        ->fields([
          'post_message' => $edit_message,
          'created_at' => $time,
        ]);

      $update->condition('id', $id);
      $result = $update->execute();

      $updated_post_query = $db->query("SELECT * FROM {dr_user_posts} WHERE id = :id", [':id' => $id]);
      $updated_post_result = $updated_post_query->fetchAll();

      $db_updated_post_message = $updated_post_result[0]->post_message;
      $db_updated_created_at = $updated_post_result[0]->created_at;
      $db_updated_post_image = $updated_post_result[0]->post_image;

      return new AjaxResponse(
        [
          "result" => "success",
          "msg" => "Success!",
          "post_message" => $db_updated_post_message,
          "post_image" => strtolower($db_updated_post_image),
          "created_at" => $db_updated_created_at
        ]);
    }

  }

}
