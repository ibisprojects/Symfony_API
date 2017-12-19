<?php

namespace API\Classes;

class Constants {

    //Error Messages
    const INVALID_TOKEN_MESSAGE = "Invalid token";
    const INVALID_REQUEST_MESSAGE = "Invalid token";
    const INVALID_USER_ID = "Invalid login";
    const INVALID_VERIFICATION_CODE = "Invalid verification code";
    const EMAIL_EXISTS_MESSAGE = "Email already registered";
    const LOGIN_EXISTS_MESSAGE = "Login string already in use";
    //Statuses
    const SUCCESS_STATUS = "Success";
    const FAILURE_STATUS = "Failed";
    //Configuration parameters
    const NAME_MIN_LENGTH = 2;
    const NAME_MAX_LENGTH = 30;
    const PASSWORD_MIN_LENGTH = 5;
    const PASSWORD_MAX_LENGTH = 15;
    const LOGIN_MIN_LENGTH = 5;
    const LOGIN_MAX_LENGTH = 15;
    
    

}
