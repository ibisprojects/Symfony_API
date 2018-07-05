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

    const NOT_SPECIFIED = " --- @@ Not Specified @@ --- ";
    const WEBSITE_CITSCI = 7;

    const GEOMETRY_TYPE_RASTER = 4;
    const AREA_SUBTYPE_POINT = 11;

    const ATTRIBUTE_PRESENCE = 15;
    const ATTRIBUTE_TYPE_VALUETYPE_LOOKUP = 1;
    const ATTRIBUTE_TYPE_VALUETYPE_FLOAT = 2;
    const ATTRIBUTE_TYPE_VALUETYPE_INTEGER = 3;

    const ATTRIBUTE_VALUE_PRESENT = 32;
    const ATTRIBUTE_VALUE_ABSENT = 33;

    const COORDINATE_SYSTEM_WGS84_GEOGRAPHIC = 1;
    const COORDINATE_SYSTEM_GOOGLE_MAPS = 341; // mercator coordinate system used by google maps

    const PROJECT_CONTRIBUTOR = 2;
    const PROJECT_MANAGER = 5;

    const ZOOM_LEVEL_MIN = 1;
    const ZOOM_LEVEL_MAX = 15;

    const INSERT_LOG_FORM = 11; // e.g., datasheets, data entry forms, etc.
    const INSERT_LOG_APP = 12; // e.g., CitSciMobile
}
