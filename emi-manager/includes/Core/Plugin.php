<?php
namespace EmiManager\Core;

use EmiManager\Services\BankService;
use EmiManager\Services\EmiCalculator;
use EmiManager\Repositories\BankRepository;
use EmiManager\Repositories\PlanRepository;
use EmiManager\Admin\AdminMenu;
use EmiManager\Admin\ProductMetaBox;
use EmiManager\Frontend\ProductDisplay;
use EmiManager\API\RestController;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static $instance = null;

    private $bank_service;
    private $emi_calculator;
    private $admin_menu;
    private $product_meta_box;
    private $product_display;
    private $rest_controller;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_services();
    }

    private function init_services(): void
    {
        $this->bank_service = new BankService(new BankRepository(), new PlanRepository());
        $this->emi_calculator = new EmiCalculator($this->bank_service);

        if (is_admin()) {
            $this->admin_menu = new AdminMenu($this->bank_service);
            $this->product_meta_box = new ProductMetaBox($this->bank_service);
        }

        $this->product_display = new ProductDisplay($this->bank_service, $this->emi_calculator);
        $this->rest_controller = new RestController($this->emi_calculator, $this->bank_service);
    }

    public function get_bank_service(): BankService
    {
        return $this->bank_service;
    }
    public function get_emi_calculator(): EmiCalculator
    {
        return $this->emi_calculator;
    }
}