<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class PageController extends Controller
{
    private function getLogo()
    {
        return ConfiguracionSitio::obtener('logo_url');
    }

    private function renderPagina($slug, $defaultTitle, $defaultSections)
    {
        $title = $defaultTitle;
        $sections = $defaultSections;

        return Inertia::render('InfoPage', [
            'title' => $title,
            'sections' => $sections,
            'logoUrl' => $this->getLogo(),
        ]);
    }

    public function nosotros()
    {
        return $this->renderPagina('nosotros', 'Quienes somos', [
            ['heading' => 'Nuestra historia', 'body' => 'Somos una tienda dedicada a acercar tecnologia y electrohogar a todo el pais.'],
            ['heading' => 'Compromiso', 'body' => 'Trabajamos con marcas confiables, garantia real y atencion personalizada.']
        ]);
    }

    public function trabajaConNosotros()
    {
        return $this->renderPagina('trabaja-con-nosotros', 'Trabaja con nosotros', [
            ['heading' => 'Oportunidades', 'body' => 'Publicamos vacantes para ventas, logistica, tecnologia y atencion al cliente.'],
            ['heading' => 'Postulacion', 'body' => 'Envianos tu CV y cuentanos en que area quieres aportar.']
        ]);
    }

    public function terminos()
    {
        return $this->renderPagina('terminos', 'Términos y condiciones', [
            ['heading' => 'Uso del sitio', 'body' => 'El uso de este sitio implica la aceptacion de nuestras condiciones comerciales.'],
            ['heading' => 'Pagos y envios', 'body' => 'Los plazos pueden variar segun disponibilidad y zona de reparto.']
        ]);
    }

    public function privacidad()
    {
        return $this->renderPagina('privacidad', 'Políticas de privacidad', [
            ['heading' => 'Datos personales', 'body' => 'Protegemos tu informacion y solo la usamos para procesar tus pedidos.'],
            ['heading' => 'Comunicaciones', 'body' => 'Solo enviamos mensajes relacionados con tu compra o promociones si autorizas.']
        ]);
    }

    public function ayuda()
    {
        return $this->renderPagina('ayuda', 'Centro de ayuda', [
            ['heading' => 'Contactanos', 'body' => 'Nuestro equipo puede ayudarte por correo o telefono en horario laboral.'],
            ['heading' => 'Pedidos', 'body' => 'Consulta el estado de tu pedido con tu codigo de compra.']
        ]);
    }

    public function devoluciones()
    {
        return $this->renderPagina('devoluciones', 'Devoluciones', [
            ['heading' => 'Politica', 'body' => 'Aceptamos devoluciones dentro de los plazos legales y con el producto en buen estado.'],
            ['heading' => 'Proceso', 'body' => 'Comunicate con soporte y comparte tu codigo de pedido.']
        ]);
    }

    public function faq()
    {
        return $this->renderPagina('faq', 'Preguntas frecuentes', [
            ['heading' => 'Envios', 'body' => 'Los tiempos de entrega dependen de la zona y disponibilidad.'],
            ['heading' => 'Pagos', 'body' => 'Aceptamos tarjetas y transferencias segun tu preferencia.']
        ]);
    }
}
