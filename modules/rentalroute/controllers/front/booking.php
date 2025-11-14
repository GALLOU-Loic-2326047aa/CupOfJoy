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
        $rental_duration = (int)Tools::getValue('rental_duration');

        if (!$id_product || $quantity <= 0 || !in_array($rental_duration, [12, 36])) {
            $this->errors[] = $this->trans('Veuillez sélectionner une durée de location valide.', [], 'Modules.Rentalroute.Shop');
            return;
        }

        $date_start = date('Y-m-d');
        $date_end = date('Y-m-d', strtotime("+$rental_duration months"));

        $total_available_quantity = StockAvailable::getQuantityAvailableByProduct($id_product);
        $sql = 'SELECT SUM(`quantity`) 
                FROM `'._DB_PREFIX_.'rentalroute_booking`
                WHERE `id_product` = '.(int)$id_product.'
                  AND `status` IN ("pending", "confirmed", "ongoing")
                  AND (`date_start` <= "'.pSQL($date_end).'" AND `date_end` >= "'.pSQL($date_start).'")';
        $booked_quantity = (int)Db::getInstance()->getValue($sql);

        if ($quantity > ($total_available_quantity - $booked_quantity)) {
            $this->errors[] = $this->trans('La quantité demandée n\'est pas disponible pour ces dates.', [], 'Modules.Rentalroute.Shop');
            return;
        }

        $rental_data = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'rental_product` WHERE `id_product` = '.(int)$id_product);
        $total_price = 0;

        if ($rental_duration == 12) {
            $total_price = $rental_data['price_per_month_12'] * 12;
        } elseif ($rental_duration == 36) {
            $total_price = $rental_data['price_per_month_36'] * 36;
        }

        $total_price += $rental_data['installation_fee'];
        $total_price *= $quantity;

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
                $redirect_url = $this->context->link->getModuleLink('rentalroute', 'booking', ['confirmation' => 1]);
                Tools::redirect($redirect_url);
            } else {
                $this->errors[] = $this->trans('Une erreur est survenue lors de l\'enregistrement de votre réservation.', [], 'Modules.Rentalroute.Shop');
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