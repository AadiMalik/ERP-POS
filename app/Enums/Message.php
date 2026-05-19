<?php

namespace App\Enums;

class  Message
{
    const SUCCESS = 'Success!';
    const FETCH = 'Record fetched successfully!';
    const SAVE = 'Record saved successfully!';
    const UPDATE = 'Record updated successfully!';
    const STATUS = 'Status updated successfully!';
    const ERROR = 'Something went wrong!';
    const DELETE = 'Record deleted successfully!';

    // NOT 
    const NOTFOUND = 'Record not found!';
    const NOTSAVE = 'Record not saved!';
    const NOTUPDATE = 'Record not updated!';
    const NOTDELETE = 'Record not deleted!';
}