<?php

require_once _PS_MODULE_DIR_ . 'rentalroute/classes/RentalBooking.php';

class rentalroutebookingModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!Tools::isSubmit('submitRental') || !$this->context->customer->isLogged()) {
            return;
        }

        $id_product = (int)Tools::getValue('id_product');
        $quantity = (int)Tools::getValue('quantity');
        $date_start = Tools::getValue('date_start');
        $date_end = Tools::getValue('date_end');

        if (!$id_product || $quantity <= 0 || !$date_start || !$date_end || strtotime($date_end) < strtotime($date_start)) {
            $this->errors[] = $this->trans('Veuillez remplir correctement la quantité et les dates.', [], 'Modules.Rentalroute.Shop');
            return;
        }

        // Calcul du prix de la location
        // 1. On récupère la quantité totale disponible depuis le stock de PrestaShop
        $total_available_quantity = StockAvailable::getQuantityAvailableByProduct($id_product);

        // 2. On calcule la quantité déjà réservée sur la période demandée
        $sql = 'SELECT SUM(`quantity`) 
                FROM `'._DB_PREFIX_.'rentalroute_booking`
                WHERE `id_product` = '.(int)$id_product.'
                  AND `status` IN ("pending", "confirmed", "ongoing")
                  AND (`date_start` <= "'.pSQL($date_end).'" AND `date_end` >= "'.pSQL($date_start).'")';

        $booked_quantity = (int)Db::getInstance()->getValue($sql);

        // 3. On vérifie si la quantité demandée est possible
        if ($quantity > ($total_available_quantity - $booked_quantity)) {
            $this->errors[] = $this->trans('La quantité demandée n\'est pas disponible pour ces dates.', [], 'Modules.Rentalroute.Shop');
            return;
        }

        // 4. On calcule la durée de la location en jours
        $startTime = strtotime($date_start);
        $endTime = strtotime($date_end);
        // On calcule la différence en secondes et on la convertit en jours
        $duration_days = round(($endTime - $startTime) / (60 * 60 * 24));
        // Une location pour la même journée doit compter comme 1 jour
        if ($duration_days < 1) {
            $duration_days = 1;
        }

        // 5. On récupère le prix de base du produit (qui sert de tarif journalier)
        // Le `false` indique qu'on veut le prix HT, le `null` utilise les taxes par défaut.
        $price_per_day = Product::getPriceStatic($id_product, false, null, 6);

        // 6. On calcule le prix total final
        $total_price = $duration_days * $price_per_day * $quantity;

        // Fin calcul prix

        try {
            $booking = new RentalBooking();
            $booking->id_product = $id_product;
            $booking->id_customer = (int)$this->context->customer->id;
            $booking->quantity = $quantity;
            $booking->date_start = $date_start;
            $booking->date_end = $date_end;
            $booking->total_price = $total_price;
            $booking->status = 'pending';

            if ($booking->add()) {
                $myRentalsUrl = $this->context->link->getModuleLink('rentalroute', 'myrentals');
                Tools::redirect($myRentalsUrl);
            } else {
                $this->errors[] = $this->trans('Une erreur est survenue lors de l\'enregistrement.', [], 'Modules.Rentalroute.Shop');
            }
        } catch (Exception $e) {
            $this->errors[] = $this->trans('Une erreur technique est survenue.', [], 'Modules.Rentalroute.Shop');
        }
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('confirmation') == 1) {
            $this->context->smarty->assign([
                'booking_success_message' => $this->trans('Votre réservation a bien été enregistrée. Veuillez payer votre location.', [], 'Modules.Rentalroute.Shop'),
                'my_rentals_url' => $this->context->link->getModuleLink('rentalroute', 'myrentals')
            ]);
        }

        $this->setTemplate('module:rentalroute/views/templates/front/booking_page.tpl');
    }
}