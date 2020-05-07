<?php

namespace App\Controller\Calidad;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Calidad\alcanceType;
use App\Entity\Calidad\alcance;
use Knp\Component\Pager\PaginatorInterface;

class alcanceController extends AbstractController {
     public function ListarAlcance(Request $request, PaginatorInterface $paginator) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $registro = $bd->getRepository(alcance::class)->findBy(array('idCompania' => $idCompania));        
        $pagination = $paginator->paginate($registro, $request->query->getInt('page', 1), 15);

        return $this->render('Calidad\riesgos/alcance\listarAlcanceRiesgo.html.twig', array(
            'reg' => $pagination));
    }

    public function NuevoAlcance() {
        $idCompania = $this->getUser()->getIdCompania();
        $alcance = new alcance();
        $FORMA = $this->createForm(alcanceType::class, $alcance, 
                array('method' => 'POST','idCompania'=>$idCompania,
                    'action' => $this->generateUrl('alcance_guardarRiesgo')));

        return $this->render('Calidad\riesgos/alcance\nuevoAlcanceRiesgo.html.twig', array(
            'form' => $FORMA->createView()));
    }

    public function guardarAlcance(Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $usuarioSession = $bd->getRepository(\App\Entity\Central\usuarios::class)->find($this->getUser()->getId());
        $alcance = new alcance();
        $FORMA = $this->createForm(alcanceType::class, $alcance, array(
            'method' => 'POST', 'idCompania'=>$idCompania,
            'action' => $this->generateUrl('alcance_guardarRiesgo')));
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {
            
            $alcance->setUsuarioCreaxAlcance($usuarioSession);
            $alcance->setFechaCrea(new \DateTime('now'));
            
            $bd->persist($alcance);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            
            return $this->redirectToRoute('listar_alcanceRiesgo');
        }
        return $this->render('Calidad\riesgos/alcance\nuevoAlcanceRiesgo.html.twig', array(
            'form' => $FORMA->createView()));
    }

    public function editarAlcance(Request $request, $idAlcance) {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $alcance = $bd->getRepository(alcance::class)->find($idAlcance);
        $FORMA = $this->createForm(alcanceType::class, $alcance, array(
            'action' => $this->generateUrl('alcance_actualizarRiesgo', array('idAlcance' => $idAlcance)),
            'method' => 'POST','idCompania'=>$idCompania,
        ));
        
        return $this->render('Calidad\riesgos/alcance\nuevoAlcanceRiesgo.html.twig', array(
            'form' => $FORMA->createView(), 'idAlcance' => $idAlcance));
    }

    public function actualizarAlcance(Request $request, $idAlcance) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $usuarioSession = $bd->getRepository(\App\Entity\Central\usuarios::class)->find($this->getUser()->getId());

        $alcance = $bd->getRepository(alcance::class)->find($idAlcance);

        $FORMA = $this->createForm(alcanceType::class, $alcance, array(
            'method' => 'POST','idCompania'=>$idCompania,
            'action' => $this->generateUrl('alcance_actualizarRiesgo', array('idAlcance' => $idAlcance))));
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {
            $alcance->setUsuarioModxAlcance($usuarioSession);
            $alcance->setFechaMod(new \DateTime('now'));
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            
            return $this->redirectToRoute('listar_alcanceRiesgo');
        }
        
        return $this->render('Calidad\riesgos/alcance\nuevoAlcanceRiesgo.html.twig', array(
            'form' => $FORMA->createView(), 'idAlcance' => $idAlcance));
    }

    public function eliminarAlcance($idAlcance) {

        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository(alcance::class)->find($idAlcance);

         try {
            $bd->remove($record);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
        return $this->redirectToRoute('listar_alcanceRiesgo');
    }
}
