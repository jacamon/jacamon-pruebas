<?php //

namespace App\Controller\Calidad;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Nomina\cargos;
use App\Form\Nomina\cargosType;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;

class cargosController extends AbstractController
{
    public function listarCargos(Request $request, PaginatorInterface $paginator)
    {
        $bd = $this->getDoctrine()->getManager();
        $idCompania=$this->getUser()->getIdCompania();
        
        $registro = $bd->getRepository('App\Entity\Nomina\cargos')->findBy(array('idCompania'=>$idCompania),array('cargo'=>'asc'));        
        $pagination = $paginator->paginate($registro, $request->query->getInt('page', 1), 15);

        return $this->render('Calidad\cargos\listarCargos.html.twig', array('reg' => $pagination));
    }
    
    public function nuevoCargos()
    {
        $cargos = new cargos();
        $idCompania = $this->getUser()->getIdCompania();
        $FORMA = $this->createForm(cargosType::class, $cargos,
                array('compania'=>$idCompania,'method' => 'POST', 'action' => $this->generateUrl('cargos_guardar')));
        return $this->render('Calidad\cargos\nuevoCargo.html.twig', array('form' => $FORMA->createView()));
    }
    
    public function guardarCargos(Request $request)
    {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        
        $cargos = new cargos();
        $FORMA = $this->createForm(cargosType::class, $cargos, 
                array('compania'=>$idCompania,'method' => 'POST', 'action' => $this->generateUrl('cargos_guardar')));
       
        $FORMA->handleRequest($request);
    
        if ($FORMA->isValid()) {
           $fechaActual = new \DateTime('now');
           $cargos->setFechaCrea($fechaActual);
           
           $usuariosxCargos = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
           $cargos->setUsuariosxCargos($usuariosxCargos);

           $companiasxCargos = $bd->getRepository('App\Entity\Central\compania')->find($this->getUser()->getIdCompania());
           $cargos->setCompaniasxCargos($companiasxCargos);
          
           $bd->persist($cargos);
           $bd->flush();
           $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
           $id=$cargos->getId();
           
           return $this->redirectToRoute('cargos_ver',array('id'=>$id));
        
        }
        return $this->render('Calidad\cargos\nuevoCargo.html.twig', array('form' => $FORMA->createView()));
    }
    
    public function verCargos($id)
    {
        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Nomina\cargos')->find($id);
        return $this->render('Calidad\cargos\verCargo.html.twig', array('id_cargo'=>$registro));
    }
    
    public function eliminarCargos($id)
    {
        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Nomina\cargos')->find($id);
         try {
            $bd->remove($registro);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
        return $this->redirectToRoute('cargos_listar');  
    }
    
    public function editarCargos($id)
    {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $cargos = $bd->getRepository('App\Entity\Nomina\cargos')->find($id);
        $FORMA = $this->createForm(cargosType::class, $cargos, array('compania'=>$idCompania,
            'action' => $this->generateUrl('cargos_actualizar', array('id' => $id)),
            'method' => 'POST'
        ));
        return $this->render('Calidad\cargos\nuevoCargo.html.twig', array('form' => $FORMA->createView(), 'id_cargo'=> $id));             
    }
    
     public function actualizarCargos(Request $request,$id )
    {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        
        $cargos = $bd->getRepository('App\Entity\Nomina\cargos')->find($id);
        $FORMA = $this->createForm(cargosType::class, $cargos, array('compania'=>$idCompania,
            'method' => 'POST', 
            'action' => $this->generateUrl('cargos_actualizar', array('id' => $id))));
        $FORMA->handleRequest($request);
        
        if ($FORMA->isValid()) {
            
            $usuariosModxCargos=$bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $cargos->setUsuariosModxCargos($usuariosModxCargos);
            $cargos->setFechaMod(new \DateTime('now'));
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
           
           return $this->redirectToRoute('cargos_ver',array('id'=>$id));
        }
        
        return $this->render('Calidad\cargos\nuevoCargo.html.twig', array('form' => $FORMA->createView(), 'id_cargo'=> $id));
    }
}
