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
    //return new AjaxResponse($data);
    //return new AjaxResponse();
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

  /** Display user's page **/
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

      //$posts_query = $db->query("SELECT * FROM {dr_user_posts} WHERE posting_to_id = :uid", [':uid' => $followee_id]);

      $posts_query = $db->query("SELECT dr_user_posts.id, dr_user_posts.poster_id, dr_user_posts.post_message, dr_user_posts.post_image,
dr_user_posts.created_at, dr_user_company_page.name_of_user,
dr_user_company_page.page_picture, dr_user_company_page.grmds_url FROM {dr_user_posts} INNER JOIN {dr_user_company_page}
ON dr_user_posts.poster_id = dr_user_company_page.uid WHERE dr_user_posts.posting_to_id = :uid ORDER BY created_at ASC", [':uid' => $followee_id]);
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

    /** Grab old values from database **/
    $old_checkUpdateQuery = $db->query("SELECT * FROM {dr_user_company_page} WHERE uid = :uid", [':uid' => $uid]);
    $old_check_result = $old_checkUpdateQuery->fetchAll();

    $db_old_name_of_user = $old_check_result[0]->name_of_user;
    $db_old_grmds_url = $old_check_result[0]->grmds_url;
    $db_old_website = $old_check_result[0]->website;
    $db_old_industry = $old_check_result[0]->industry;
    $db_old_company_size = $old_check_result[0]->company_size;
    $db_old_company_type = $old_check_result[0]->company_type;
    $db_old_headquarters = $old_check_result[0]->headquarters;
    $db_old_founded = $old_check_result[0]->founded;
    $db_old_page_picture = $old_check_result[0]->page_picture;
    $db_old_tagline = $old_check_result[0]->tagline;
    //$db_old_time = $old_check_result[0]->updated_at;

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
       'page_picture' => $upload_picture,
       'tagline' => $tagline,
       'updated_at' => $time,
     ])
     ->condition('uid', $uid);
     $result = $update_query->execute();

     /** Grab new values from database **/
     $new_checkUpdateQuery = $db->query("SELECT * FROM {dr_user_company_page} WHERE uid = :uid", [':uid' => $uid]);
     $new_check_result = $new_checkUpdateQuery->fetchAll();

     $db_new_name_of_user = $new_check_result[0]->name_of_user;
     $db_new_grmds_url = $new_check_result[0]->grmds_url;
     $db_new_website = $new_check_result[0]->website;
     $db_new_industry = $new_check_result[0]->industry;
     $db_new_company_size = $new_check_result[0]->company_size;
     $db_new_company_type = $new_check_result[0]->company_type;
     $db_new_headquarters = $new_check_result[0]->headquarters;
     $db_new_founded = $new_check_result[0]->founded;
     $db_new_page_picture = $new_check_result[0]->page_picture;
     $db_new_tagline = $new_check_result[0]->tagline;
     //$db_new_time = $new_check_result[0]->updated_at;


     if($db_old_name_of_user !== $db_new_name_of_user || $db_old_grmds_url !== $db_new_grmds_url ||
        $db_old_website !== $db_new_website || $db_old_industry !== $db_new_industry || $db_old_company_size !== $db_new_company_size ||
        $db_old_company_type !== $db_new_company_type || $db_old_headquarters !== $db_new_headquarters || $db_old_founded !== $db_new_founded ||
        $db_old_page_picture !== $db_new_page_picture || $db_old_tagline !== $db_new_tagline){

       return new AjaxResponse(['result' => 'success', 'url' => $db_new_grmds_url]);

     } else {
       return new AjaxResponse(['result' => 'error', 'msg' => 'No changes made.']);
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

  public function createPosts($postingToId){

    /** Create database connection and grab current logged in user ID **/
    $db = Database::getConnection();
    $current_loggedin_user_ID = \Drupal::currentUser()->id();

    $time = time();

    /** Create php variables from ajax request **/
    //$poster_company_name = $_POST['postCompanyName'];
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

            $insert_update = $db->upsert('dr_user_posts')
              ->fields([
                'poster_id' => $current_loggedin_user_ID,
                'posting_to_id' => $postingToId,
                'post_image' => $final_image,
                'post_message' => $poster_company_message,
                'created_at' => $time,
              ]);

            $insert_update->key('poster_id');
            $result = $insert_update->execute();

            return new AjaxResponse(["result" => "success", "msg" => "Success!"]);

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
      $insert_update = $db->upsert('dr_user_posts')
        ->fields([
          'poster_id' => $current_loggedin_user_ID,
          'posting_to_id' => $postingToId,
          'post_image' => $poster_image,
          'post_message' => $poster_company_message,
          'created_at' => $time,
        ]);

      $insert_update->key('poster_id');
      $result = $insert_update->execute();

      return new AjaxResponse(["result" => "success", "msg" => "Success!"]);
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

    return new AjaxResponse(
      [
        'result' => 'success',
        'id' => $edit_results[0]->id,
        'edit_post_company_name' => $edit_results[0]->company_name,
        'edit_post_message' => $edit_results[0]->post_message,
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

            return new AjaxResponse(["result" => "success", "msg" => "Success!"]);

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

      return new AjaxResponse(["result" => "success", "msg" => "Success!"]);
    }

  }

}
