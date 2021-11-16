<?php
namespace Verba;

interface BlockInterface {
    function route();

    function prepare();

    function prepareItems();

    function build();

    function buildItems();

    function init();

    function output();

    function getRequest();

    function isMuted();
}