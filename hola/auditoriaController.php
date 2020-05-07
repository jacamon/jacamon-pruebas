<?php

namespace App\Controller\Calidad;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Calidad\auditorias;
use App\Entity\Calidad\procesosxauditoria;
use App\Entity\Calidad\auditoresxproceso;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Calidad\conceptosauditoria;
use App\Entity\Calidad\ejecucionauditoria;
use App\Entity\Calidad\tiposauditoria;
use App\Entity\Calidad\requisitosxconceptosauditoria;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\Central\servNotificaciones;

class auditoriaController extends AbstractController {

    public function listarAuditoria(Request $request, PaginatorInterface $paginator) {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();

        if ($request->query->get('form')) {
            $valores = $request->query->get('form');
            $fechaini = $valores["fecha_ini"];
            $fechafin = $valores["fecha_fin"];
            $tipoauditoria = $valores["tiposauditoriaxAuditorias"];
        }
        #Filtros que cargan por defecto apenas se abre la pagina
        else {

            $fechai = new \DateTime();
            $fechaini = $fechai->format('Y-m-01');
            $fechaf = new \DateTime();
            $fechafin = $fechaf->format('Y-m-d');
        }
        if(!empty($tipoauditoria)){
            $adTipoAdutio="and a.idTipoauditoria=:TipoAuditoria";
        }else{
            $tipoauditoria=0;
            $adTipoAdutio="";
        }
        
        $dql = "select a from App\Entity\Calidad\auditorias a WHERE a.fecha"
                . " between '$fechaini 00:00:00' and '$fechafin 23:59:59' AND a.idCompania=:Compania"
                . " $adTipoAdutio  order by a.id ";
        $query = $bd->createQuery($dql);
        $query->setParameter(':Compania', $idCompania);
        
        if(!empty($tipoauditoria)){
        $query->setParameter(':TipoAuditoria', $tipoauditoria);
        }
                 
        $pagination = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $registro = $query->getResult();

        $FORMA = $this->createFormBuilder($registro, array('csrf_protection' => false))
                ->add('fecha_ini', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Desde', 'widget' => 'single_text'))
                ->add('fecha_fin', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Hasta', 'widget' => 'single_text'))
                ->add('tiposauditoriaxAuditorias', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\\tiposauditoria',
                    'choice_value' => 'id',
                    'choice_label' => 'tipoauditoria',
                    'label' => 'Tipo Auditoria',
                    'placeholder'=>"Seleccione una opcion",
                    'data'=>$bd->getRepository('App\Entity\Calidad\\tiposauditoria')->find($tipoauditoria),
                    'query_builder' => function(\App\Repository\Calidad\tiposauditoriaRepository $er ) use ( $idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :A')
                                ->setParameter('A', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    },
                ))
                ->add('filtrar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'filtrar'))
                ->setMethod('GET')
                ->getForm();
                    
                  
        $perfil[] = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(array('idPerfil' =>  $this->getUser()->getIdrol(),'idCompania'=>$idCompania));
        
        if(empty($perfil)){
            $perfil=0;
        }
        return $this->render('Calidad\auditoria\listarAuditorias.html.twig', array('reg' => $pagination, 'form' => $FORMA->createView(),
                    'fecha_ini' => $fechaini, 'fecha_fin' => $fechafin,'perfil'=>$perfil));
    }

    public function nuevoAuditoria() {
        $auditoria = new auditorias();
        $fechaexiste = 0;
        $idCompania=$this->getUser()->getIdCompania();
        $FORMA = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar')))
                ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Nombre'))
                ->add('tiposauditoriaxAuditorias', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\\tiposauditoria',
                    'choice_value' => 'id',
                    'choice_label' => 'tipoauditoria',
                    'label' => 'Tipo Auditoria',
                    'query_builder' => function(\App\Repository\Calidad\tiposauditoriaRepository $er ) use ( $idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :A')
                                ->setParameter('A', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    },
                ))
                ->add('fecha', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Fecha', 'widget' => 'single_text','data'=>new \DateTime('now')))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
                    
        return $this->render('Calidad\auditoria\nuevaAuditoria.html.twig', array('form' => $FORMA->createView(), 'fechaexiste' => $fechaexiste));
    }

    public function guardarAuditoria(Request $request) {
  
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $fechaexiste = 0;
        $auditoria = new auditorias();
        $FORMA = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar')))
                ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Nombre'))
                ->add('tiposauditoriaxAuditorias', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\\tiposauditoria',
                    'choice_value' => 'id',
                    'choice_label' => 'tipoauditoria',
                    'label' => 'Tipo Auditoria',
                    'query_builder' => function(\App\Repository\Calidad\tiposauditoriaRepository $er ) use ( $idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :A')
                                ->setParameter('A', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    },
                ))     
                ->add('fecha', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Fecha', 'widget' => 'single_text'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA->handleRequest($request);
        $auditoriaP = $request->request->get('form');
        $valorFecha = $auditoriaP['fecha'];


        $dql = "select a from App\Entity\Calidad\auditorias a WHERE a.fecha"
                . " between '$valorFecha 00:00:00' and '$valorFecha 23:59:59' AND a.idCompania=:Compania order by a.id ";
        $query = $bd->createQuery($dql);
        $query->setParameter(':Compania', $idCompania);

        $audito = $query->getResult();


        if (!empty($audito)) {
            foreach ($audito as $audi) {
                $fechaaudi[] = $audi->getFecha();
            }
        }

        if (!empty($fechaaudi)) {
            $fechaexiste = 1;
        }


        if ($FORMA->isSubmitted() and $fechaexiste != 1) {
            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(1));
            $auditoria->setCompaniasxAuditorias($bd->getRepository('App\Entity\Central\compania')->find($this->getUser()->getIdCompania()));
            $auditoria->setUsuariocreaxAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            $auditoria->setFechaCrea(new \DateTime('now'));
            $bd->persist($auditoria);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('auditoria_listar');
        }
        return $this->render('Calidad\auditoria\nuevaAuditoria.html.twig', array('form' => $FORMA->createView(), 'fechaexiste' => $fechaexiste));
    }

    public function verAuditoria($idAuditoria) {
        $bd = $this->getDoctrine()->getManager();
        $auditoria = $bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria);
        $idCompania = $this->getUser()->getIdCompania();
        $procesosxauditoria = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->findBy(array('idAuditoria' => $idAuditoria));
        foreach ($procesosxauditoria as $valor) {
            $idProcesoxauditoria = $valor->getId();
        }
        $auditoresxproceso = $bd->getRepository('App\Entity\Calidad\auditoresxproceso')->findAll();

        $FORMA = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_reprogramar', array('idAuditoria' => $idAuditoria))))
                ->add('motivo_reprograma', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Motivo reprogramación'))
                ->add('fecha', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Fecha', 'widget' => 'single_text'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA1 = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_cancelar', array('idAuditoria' => $idAuditoria))))
                ->add('motivo_cancela', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Motivo cancelación'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA2 = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_vobo', array('idAuditoria' => $idAuditoria))))
                ->add('nota_acepta', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Nota'))
                ->add('acepta', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array('choices' => array(
                        'Aceptar' => '1',
                        'Rechazar' => '0')))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA3 = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_cierre', array('idAuditoria' => $idAuditoria))))
                ->add('nota_cierre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Nota de cierre'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();
        
        $perfil[] = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(array('idPerfil' =>  $this->getUser()->getIdrol(),'idCompania'=>$idCompania));
        
        if(empty($perfil)){
            $perfil=0;
        }
        
        return $this->render('Calidad\auditoria\verAuditoria.html.twig', array('auditoria' => $auditoria,
                'form' => $FORMA->createView(),
                'form1' => $FORMA1->createView(),
                'form2' => $FORMA2->createView(),
                'form3' => $FORMA3->createView(),
                'procesosxauditoria' => $procesosxauditoria,
                'auditoresxproceso' => $auditoresxproceso,
                'perfil'=>$perfil
        ));
    }

    public function controlAuditoria(Request $request, $idAuditoria, servNotificaciones $notifica) {

        $bd = $this->getDoctrine()->getManager();
        $auditoria = $bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria);
        $idCompania = $this->getUser()->getIdCompania();
        $usuariocrea = $auditoria->getUsucrea();

        $usuariocre = $bd->getRepository('App\Entity\Central\usuarios')->find($usuariocrea);
        $CorreoCrea[] = $usuariocre->getId();
        $valores = $request->request->get('form');
        $FORMA = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_reprogramar', array('idAuditoria' => $idAuditoria))))
                ->add('motivo_reprograma', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Motivo reprogramación'))
                ->add('fecha', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Fecha', 'widget' => 'single_text'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA1 = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_cancelar', array('idAuditoria' => $idAuditoria))))
                ->add('motivo_cancela', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Motivo cancelación'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA2 = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_vobo', array('idAuditoria' => $idAuditoria))))
                ->add('nota_acepta', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Nota'))
                ->add('acepta', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array('choices' => array(
                        'Aceptar' => '1',
                        'Rechazar' => '0')))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();
        $FORMA3 = $this->createFormBuilder($auditoria, array(
                    'method' => 'POST',
                    'action' => $this->generateUrl('auditoria_cierre', array('idAuditoria' => $idAuditoria))))
                ->add('nota_cierre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Nota de cierre'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();
        if (!empty($valores['motivo_reprograma'])) {

            $FORMA->handleRequest($request);
        }
        if (!empty($valores['motivo_cancela'])) {

            $FORMA1->handleRequest($request);
        }
        if (!empty($valores['nota_acepta'])) {

            $FORMA2->handleRequest($request);
        }
        if (!empty($valores['nota_cierre'])) {

            $FORMA3->handleRequest($request);
        }

        #Modal de Reprogramar
        if ($FORMA->isSubmitted() && $FORMA->isValid()) {
            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(1));
            $auditoria->setUsuariomodxAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            $auditoria->setFechamod(new \DateTime('now'));
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
        }
        #Modal dde Cancelar
        else if ($FORMA1->isSubmitted() && $FORMA1->isValid()) {
            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(8));
            $auditoria->setUsuariocancelaxAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            $auditoria->setFechamod(new \DateTime('now'));
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
        }
        #modal Vobo
        else if ($FORMA2->isSubmitted() && $FORMA2->isValid()) {
            if ($valores['acepta'] == 1) {

                $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(5));
                $msj = "Solicitud de aceptacion de auditoria ";
                $auditoresdeProceso = $bd->getRepository('App\Entity\Calidad\auditoresxproceso')->findAll();
                $CorreoAuditoresProceso=[];
                if ($auditoresdeProceso) {
                    foreach ($auditoresdeProceso as $valorAuditores) {
                        $DatosAuditores = $bd->getRepository('App\Entity\Central\usuarios')->find($valorAuditores->getIdUsuario());
                        if ($DatosAuditores) {
                            $CorreoAuditoresProceso[] = $DatosAuditores->getId();
                        }
                    }
                    if (count($CorreoAuditoresProceso)>0) {
                        
                        $not1 = $notifica->crearNotificacion($CorreoAuditoresProceso, 
                        'Notificacion de aceptación de auditoria', 
                        'Este mensaje indica que se ha aceptado la Auditoria.<br>
                        Auditoria: '.$auditoria->getNombre().'', 
                        'auditoria_ver', 
                        1, 
                        'idAuditoria:'.$auditoria->getId().''); 
                        
//                    $message = (new \Swift_Message($msj))
//                    ->setFrom('compucontadesarrollo@gmail.com')
//                    ->setTo($CorreoAuditoresProceso)
//                    ->setBody(
//                    $this->renderView(
//                            'Calidad\email\notificacionAceptaAuditoria.html.twig', array('Gestor' => $DatosAuditores, 'auditoria' => $auditoria)
//                    ), 'text/html'
//                    )
//                    ;
//                    $this->get('mailer')->send($message);
                    }
                }
            } else {
                $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(3));
                $msj = "solicitud de rechazo de auditoria ";
            }
            $auditoria->setUsuariomodxAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            $auditoria->setFechamod(new \DateTime('now'));

            if (count($CorreoCrea)>0) {
                
                    $not1 = $notifica->crearNotificacion($CorreoCrea, 
                    'Notificacion de Auditoria', 
                    'Este mensaje indica la '.$msj.'<br>
                    Auditoria: '.$auditoria->getNombre().'', 
                    'auditoria_ver', 
                    1, 
                    'idAuditoria:'.$auditoria->getId().'');  
//                $message = (new \Swift_Message($msj))
//                ->setFrom('compucontadesarrollo@gmail.com')
//                ->setTo($CorreoCrea)
//                ->setBody(
//                $this->renderView(
//                        'Calidad\email\notificacionAuditoriaInterna.html.twig', array('mensaje' => $msj, 'Gestor' => $usuariocre, 'auditoria' => $auditoria)
//                ), 'text/html'
//                );
//                $this->get('mailer')->send($message);
            }

            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
        }
        #modal Cierre
        else if ($FORMA3->isSubmitted() && $FORMA3->isValid()) {
       
            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(9));
            $auditoria->setUsuariomodxAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            $auditoria->setFechamod(new \DateTime('now'));

            $auditoria->setUsuariocierrexAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            $auditoria->setFechacierre(new \DateTime('now'));

            if (count($CorreoCrea)>0) {
                
                    $not1 = $notifica->crearNotificacion($CorreoCrea, 
                    'Notificacion de Auditoria', 
                    'Este mensaje notifica el cierre de auditoría<br>
                    Auditoria: '.$auditoria->getNombre().'', 
                    'auditoria_ver', 
                    1, 
                    'idAuditoria:'.$auditoria->getId().'');   
//                $message = (new \Swift_Message('notificacion de cierre de Auditoria'))
//                ->setFrom('compucontadesarrollo@gmail.com')
//                ->setTo($CorreoCrea)
//                ->setBody(
//                $this->renderView(
//                        'Calidad\email\notificacionAuditoriaInterna.html.twig', array('mensaje' => "notificacion de cierre de Auditoria", 'Gestor' => $usuariocre, 'auditoria' => $auditoria)
//                ), 'text/html'
//                );
//                $this->get('mailer')->send($message);
            }
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
        }


        return $this->render('Calidad\auditoria\verAuditoria.html.twig', array('auditoria' => $auditoria,
                    'form' => $FORMA->createView(),
                    'form1' => $FORMA1->createView(),
                    'form2' => $FORMA2->createView(),
                    'form3' => $FORMA3->createView()
        ));
    }

    public function solicitaVoboAuditoria($idAuditoria, servNotificaciones $notifica) {
        $bd = $this->getDoctrine()->getManager();
        $auditoria = $bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria);
        $idCompania = $this->getUser()->getIdCompania();
        $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(4));

        $auditoria->setUsuariomodxAuditorias($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
        $auditoria->setFechamod(new \DateTime('now'));
        
        $perfil[] = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(array('idFuncionalidad' => 885, 'seguimiento' => true,'idCompania'=>$idCompania));

            if ($perfil) {
                foreach ($perfil[0] as $value) {
                    $usuarios[] = $bd->getRepository('App\Entity\Central\usuarios')->findBy(['idrol' => $value->getIdPerfil(), 'activo' => TRUE]);
                }
            }
            if (!empty($usuarios)) {
                foreach ($usuarios[0] as $usuario) {
                    $mail[] = $usuario->getId();
                }
                 if (count($mail)>0) {
                    $not1 = $notifica->crearNotificacion($mail, 
                    'Notificacion de Auditoria', 
                    'Este mensaje notifica la solicitud de aprobación de auditoría<br>
                    Auditoria: '.$auditoria->getNombre().'', 
                    'auditoria_ver', 
                    1, 
                    'idAuditoria:'.$auditoria->getId().'');      
                        
//                        $message = (new \Swift_Message('Solicitud de aprobacion de auditoria'))
//                        ->setFrom('compucontadesarrollo@gmail.com')
//                        ->setTo($mail)
//                        ->setBody(
//                        $this->renderView(
//                                'Calidad\email\notificacionAuditoriaInterna.html.twig', array('mensaje' => "Solicitud de aprobacion de auditoria", 'Gestor' => $usuario, 'auditoria' => $auditoria)
//                        ), 'text/html'
//                        )
//                        ;
//                        $this->get('mailer')->send($message);
                    }
            }
        
        
        
        
//        $Datosgestor = $bd->getRepository('App\Entity\Central\usuarios')->findBy(array('rol' => '**ROLE_ADMIN**', 'activo' => 'TRUE'));
//
//        if ($Datosgestor) {
//            foreach ($Datosgestor as $value) {
//                $CorreoGestor = $value->getMail();
//
//                if ($CorreoGestor) {
//                    $message = (new \Swift_Message('Solicitud de aprobacion de auditoria'))
//                            ->setFrom('compucontadesarrollo@gmail.com')
//                            ->setTo($CorreoGestor)
//                            ->setBody(
//                            $this->renderView(
//                                    'App\Entity\Calidad\email:notificacionAuditoriaInterna.html.twig', array('mensaje' => "Solicitud de aprobacion de auditoria", 'Gestor' => $value, 'auditoria' => $auditoria)
//                            ), 'text/html'
//                            )
//                    ;
//                    $this->get('mailer')->send($message);
//                }
//            }
//        }
        $bd->flush();
        $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
        return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
    }

    public function anadirprocesosAuditoria($idAuditoria) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $dql = "select a from App\Entity\Calidad\procesosxauditoria a where a.idAuditoria=:Audita";
        $query = $bd->createQuery($dql);
        $query->setParameter(':Audita', $idAuditoria);
        $registro = $query->getResult();


        foreach ($registro as $re) {
            $dataProcesos[$re->getId()] = $re->getIdProceso();
            $dataSubProcesos[$re->getId()] = $re->getIdSubproceso();
            $DataId[] = $re->getId();
        }

        #Si existe un proceso y no tiene sub procesos
        if (!empty($DataId)) {
            foreach ($DataId as $vid) {
                if ($dataProcesos[$vid] != "" and $dataSubProcesos[$vid] == null) {
                    $VectorProcesos[] = $dataProcesos[$vid];
                }

                if ($dataProcesos[$vid] != "" and $dataSubProcesos[$vid] != null) {
                    $subprocesos = $bd->getRepository('App\Entity\Calidad\subprocesos')->findBy(array('idProceso' => $dataProcesos[$vid]));
                }
                if (!empty($subprocesos)) {
                    foreach ($subprocesos as $subpro) {
                        $VectorSubprocesos[] = $subpro->getId();
                    }
                }
                if (!empty($VectorSubprocesos)) {
                    if (count(array_filter($dataSubProcesos)) == count(array_filter($VectorSubprocesos))) {
                        $VectorProcesos[] = $dataProcesos[$vid];
                    }
                }
            }
        }

        if (empty($VectorProcesos)) {
            $VectorProcesos = 0;
        }

        $auditoria = $bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria);
        $FORMA = $this->createFormBuilder($registro, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_procesos', array('idAuditoria' => $idAuditoria))))
                ->add('proceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\procesos',
                    'choice_value' => 'id',
                    'choice_label' => 'proceso',
                    'label' => 'Procesos',
                    'query_builder' => function(\App\Repository\Calidad\procesosRepository $er ) use ( $idCompania, $VectorProcesos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :A')
                                ->andwhere('w.id NOT IN (:B)')
                                ->setParameter('A', $idCompania)
                                ->setParameter('B', $VectorProcesos)
                                ->orderBy('w.id', 'ASC');
                    },
                    'multiple' => true
                ))
                ->add('usuario', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Central\usuarios',
                    'choice_value' => 'id',
                    'choice_label' => 'nombre',
                    'label' => 'Auditores',
                    'multiple' => true
                ))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        return $this->render('Calidad\auditoria\añadirProcesos.html.twig', array('auditoriaProcesos' => $registro, 'form' => $FORMA->createView(),
                    'auditoria' => $auditoria
        ));
    }

    public function guardarprocesosAuditoria(Request $request, $idAuditoria) {
       
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();

        $dql = "select a from App\Entity\Calidad\procesosxauditoria a "
                . "left JOIN a.auditoresxprocesoxProcesosxauditorias b where a.id=:Audita";
        $query = $bd->createQuery($dql);
        $query->setParameter(':Audita', $idAuditoria);
        $registro = $query->getResult();
        $auditoria = $bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria);
        $procesosxaudito = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->findBy(array('idAuditoria' => $idAuditoria));
// dump($request);
//        exit;
        foreach ($procesosxaudito as $procaudito) {
            $datosProcesosxaudito[] = $procaudito->getId();
        }

        if (empty($datosProcesosxaudito)) {
            $datosProcesosxaudito = 0;
        }
        $FORMA = $this->createFormBuilder($registro, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_procesos', array('idAuditoria' => $idAuditoria))))
                ->add('proceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\procesos',
                    'choice_value' => 'id',
                    'choice_label' => 'proceso',
                    'label' => 'Procesos',
                    'query_builder' => function(\App\Repository\Calidad\procesosRepository $er ) use ( $idCompania, $datosProcesosxaudito ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->andwhere('w.id NOT IN (:b)')
                                ->setParameter('a', $idCompania)
                                ->setParameter('b', $datosProcesosxaudito)
                                ->orderBy('w.id', 'ASC');
                    },
                    'multiple' => true
                ))
                ->add('usuario', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Central\usuarios',
                    'choice_value' => 'id',
                    'choice_label' => 'nombre',
                    'label' => 'Auditores',
                    'multiple' => true
                ))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
        $FORMA->handleRequest($request);

        $valores = $request->request->get('form');
        $valoressubprocesos = $request->request->get('subprocesos_multiple');

        if ($FORMA->isSubmitted() && $FORMA->isValid()) {

            if (!empty($valoressubprocesos)) {
                foreach ($valoressubprocesos as $subprocesosxauditoriax) {
                    $procesosxauditoria = new procesosxauditoria();
                    $procesosxauditoria->setAuditoriasxProcesosxauditorias($bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria));
                    $procesosxauditoria->setProcesosxProcesosxauditorias($bd->getRepository('App\Entity\Calidad\procesos')->find($valores['proceso'][0]));
                    $procesosxauditoria->setSubprocesosxProcesosxauditorias($bd->getRepository('App\Entity\Calidad\subprocesos')->find($subprocesosxauditoriax));
                    $bd->persist($procesosxauditoria);
                    $bd->flush();
                    $id_procesoAuditoria = $procesosxauditoria->getId();

                    foreach ($valores['usuario'] as $auditoriesprocesox) {
                        $auditoresxproceso = new auditoresxproceso();
                        $auditoresxproceso->setProcesosxauditoriasxAuditoresxproceso($bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($id_procesoAuditoria));
                        $auditoresxproceso->setUsuariosxAuditoresxproceso($bd->getRepository('App\Entity\Central\usuarios')->find($auditoriesprocesox));
                        $bd->persist($auditoresxproceso);
                        $bd->flush();
                    }
                }
            } else {

                foreach ($valores['proceso'] as $procesosxauditoriax) {
                    $procesosxauditoria = new procesosxauditoria();
                    $procesosxauditoria->setAuditoriasxProcesosxauditorias($bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria));
                    $procesosxauditoria->setProcesosxProcesosxauditorias($bd->getRepository('App\Entity\Calidad\procesos')->find($procesosxauditoriax));
                    $bd->persist($procesosxauditoria);
                    $bd->flush();
                    $id_procesoAuditoria = $procesosxauditoria->getId();

                    foreach ($valores['usuario'] as $auditoriesprocesox) {
                        $auditoresxproceso = new auditoresxproceso();
                        $auditoresxproceso->setProcesosxauditoriasxAuditoresxproceso($bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($id_procesoAuditoria));
                        $auditoresxproceso->setUsuariosxAuditoresxproceso($bd->getRepository('App\Entity\Central\usuarios')->find($auditoriesprocesox));
                        $bd->persist($auditoresxproceso);
                        $bd->flush();
                    }
                }
            }

            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(2));
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
        }

        return $this->render('Calidad\auditoria\añadirProcesos.html.twig', array('auditoriaProcesos' => $registro, 'form' => $FORMA->createView(),
                    'auditoria' => $auditoria
        ));
    }

    public function cargarSubprocesosAuditoria(Request $request) {
        $valorProceso = $request->request->get('proceso');
        $idAuditoria = $request->request->get('idauditoria');
        $bd = $this->getDoctrine()->getManager();
        $procesoxauditoria = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->findBy(array('idProceso' => $valorProceso, 'idAuditoria' => $idAuditoria));


        foreach ($procesoxauditoria as $pxa) {
            $var[] = $pxa->getIdSubproceso();
        }
        if (empty($var)) {
            $var = array(0);
        }
        $dql = "select a from App\Entity\Calidad\subprocesos a where a.id not in (:Valores) and a.idProceso=:Proceso";
        $query = $bd->createQuery($dql);
        $query->setParameter(':Valores', array_values($var));
        $query->setParameter(':Proceso', $valorProceso);
        $subprocesos = $query->getResult();

        foreach ($subprocesos as $subpro) {
            $DatosSubprocesos[$subpro->getId()] = $subpro->getSubproceso();
        }
        if (empty($DatosSubprocesos)) {
            $DatosSubprocesos = 0;
        }
        return new JsonResponse($DatosSubprocesos);
    }

    public function eliminarprocesosAuditoria($idProcesoxAuditoria) {
        $bd = $this->getDoctrine()->getManager();
        $audixproce = $bd->getRepository('App\Entity\Calidad\auditoresxproceso')->findBy(
                array('idProcesoxauditoria' => $idProcesoxAuditoria));
        foreach ($audixproce as $auxp) {
            $bd->remove($auxp);
        }

        $ProcxAudi = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria);
        $idAuditoria = $ProcxAudi->getIdAuditoria();
        try {
            $bd->remove($ProcxAudi);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
       
        return $this->redirectToRoute('auditoria_ver', array('idAuditoria' => $idAuditoria));
    }

    public function planejecucionAuditoria($idProcesoxAuditoria) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $procesosxauditorias = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria);
        $auditoresxproceso = $bd->getRepository('App\Entity\Calidad\auditoresxproceso')->findBy(array('idProcesoxauditoria' => $idProcesoxAuditoria));

        $idAuditoria = $procesosxauditorias->getIdAuditoria();
        $todosconceptosauditoria = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->findAll();

        foreach ($todosconceptosauditoria as $concepauditoria) {
            $vectoralAuditoriasxconceptos[] = $concepauditoria->getProcesosxauditoriasxConceptosauditoria()->getIdAuditoria();
            $vectoralProcesosxconceptos[] = $concepauditoria->getProcesosxauditoriasxConceptosauditoria()->getIdProceso();
            $vectoralSubProcesosxconceptos[] = $concepauditoria->getProcesosxauditoriasxConceptosauditoria()->getIdSubproceso();
        }
        if (empty($vectoralAuditoriasxconceptos)) {
            $vectoralAuditoriasxconceptos = 0;
        }
        if (empty($vectoralProcesosxconceptos)) {
            $vectoralProcesosxconceptos = 0;
        }
        if (empty($vectoralSubProcesosxconceptos)) {
            $vectoralSubProcesosxconceptos = 0;
        }
        
         $dql = "select a from App\Entity\Calidad\conceptosauditoria a left join a.requisitosxConceptosAuditoria b "
                . " where a.idProcesoxauditoria=:X ";
            $query = $bd->createQuery($dql);
            $query->setParameter(':X', $idProcesoxAuditoria);
            $conceptosauditoria = $query->getResult();
        //$conceptosauditoria = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->findBy(array('idProcesoxauditoria' => $idProcesoxAuditoria));


        $proceso = $procesosxauditorias->getIdProceso();
        $subproceso = $procesosxauditorias->getIdSubproceso();
        if (!$subproceso) {
            $subproceso = 0;
        }
        $Caracterizacion=$bd->getRepository('App\Entity\Calidad\caracterizacion')->findBy(array('idProceso' => $proceso,'estado'=>1));
        $arrayReq=[];
        if(count($Caracterizacion)>0){
            $idCaracterizacion=$Caracterizacion[0]->getId();
            $requisitos=$bd->getRepository('App\Entity\Calidad\\requisitosxProceso')->findBy(array('idCaracterizacion' => $idCaracterizacion));
            foreach ($requisitos as $value) {
               $arrayReq[]=$value->getRequisito();
            }
         
        }
        
      //  dump($arrayReq); exit;
        
        $dql1="select a from App\Entity\Calidad\\requisitos a WHERE a.id IN (:arrayReq) ";
        $query1 = $bd->createQuery($dql1);
        $query1->setParameter('arrayReq', $arrayReq);
        $arrayRequisitos = $query1->getResult();
        
        $FORMA = $this->createFormBuilder($conceptosauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('concepto', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Concepto'))
//                ->add('requisitosxConceptos', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
//                    'class' => 'App\Entity\Calidad\\requisitos',
//                    'choice_value' => 'id',
//                    'choice_label' => 'requisito',
//                    'label' => 'Requisitos',
//                    'placeholder'=>'Seleccione Requisito',
//                    'required'=>false,
//                    'multiple'=>true,
//                    'attr' => array(
//                        'class' => 'form-control selectpicker',
//                            'data-live-search' => true,
//                    ),     
//                    'query_builder' => function(\App\Repository\Calidad\requisitosRepository $er ) use ($arrayRequisitos) {
//                        return $er->createQueryBuilder('w')
//                                ->Where('w.id in (:B)')
//                                ->setParameter('B', $arrayRequisitos)
//                                ->orderBy('w.id', 'ASC');
//                    },
//                   
//                ))
                
               
                ->add('campo1', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\\requisitos',
                    'choice_value' => 'id',
                    'choice_label' => 'requisito',
                    'label' => 'Requisitos',
                    //'required'=>false,
                    'multiple'=>true,
                   //'attr' => array(
                   //    'class' => 'selectpicker',
                       //'data-live-search' => true,
                    //), 
                    'query_builder' => function(\App\Repository\Calidad\requisitosRepository $er ) use ($arrayRequisitos) {
                        return $er->createQueryBuilder('w')
                                ->Where('w.id in (:B)')
                                ->setParameter('B', $arrayRequisitos)
                                ->orderBy('w.id', 'ASC');
                    },
                    ))               
              //  ->add('campo1', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label'=>'prueba'))            
                ->add('critico', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, array('label' => 'Critico', 'required' => false))
                ->add('guardar3', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => '+'))
                ->getForm();

        $FORMA1 = $this->createFormBuilder($conceptosauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_traerprocesos', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('SelAuditoria', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\auditorias',
                    'choice_value' => 'id',
                    'choice_label' => 'nombre',
                    'label' => 'Auditorias',
                    'placeholder' => 'Seleccione una auditoria',
                    'data' => $bd->getRepository("App\Entity\Calidad\auditorias")->find($idAuditoria),
                    'query_builder' => function(\App\Repository\Calidad\auditoriasRepository $er ) use ($idCompania, $vectoralAuditoriasxconceptos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->andWhere('w.id in (:B)')
                                ->setParameter('a', $idCompania)
                                ->setParameter('B', $vectoralAuditoriasxconceptos)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('SelProceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\procesos',
                    'choice_value' => 'id',
                    'choice_label' => 'proceso',
                    'label' => 'Procesos',
                    'placeholder' => 'Seleccione un Proceso',
                    'data' => $bd->getRepository("App\Entity\Calidad\procesos")->find($proceso),
                    'query_builder' => function(\App\Repository\Calidad\procesosRepository $er ) use ( $idCompania, $vectoralProcesosxconceptos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->andWhere('w.id in (:B)')
                                ->setParameter('a', $idCompania)
                                ->setParameter('B', $vectoralProcesosxconceptos)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('subproceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\subprocesos',
                    'choice_value' => 'id',
                    'choice_label' => 'subproceso',
                    'label' => 'SubProcesos',
                    'query_builder' => function(\App\Repository\Calidad\subprocesosRepository $er ) use ( $proceso, $vectoralSubProcesosxconceptos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idProceso = :a')
                                ->andWhere('w.id in (:B)')
                                ->setParameter('a', $proceso)
                                ->setParameter('B', $vectoralSubProcesosxconceptos)
                                ->orderBy('w.id', 'ASC');
                    },
                    'data' => $bd->getRepository("App\Entity\Calidad\subprocesos")->find($subproceso)
                ))
                ->add('guardar2', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA2 = $this->createFormBuilder($conceptosauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('archivo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, 
                        array('label' => 'Subir archivo plano',
                            'attr' => array(
                                    'accept' => 'text/plain,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel'
                                ),))
                ->add('guardar1', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        return $this->render('Calidad\auditoria\planAuditoria.html.twig', array('procesoxauditoria' => $procesosxauditorias,
                    'auditoresxproceso' => $auditoresxproceso,
                    'conceptosauditoria' => $conceptosauditoria,
                    'requisitos'=>$arrayRequisitos,
                    'form' => $FORMA->createView(),
                    'form1' => $FORMA1->createView(),
                    'form2' => $FORMA2->createView()
        ));
    }

    public function guardarplanejecucionAuditoria(Request $request, $idProcesoxAuditoria) {
        $bd = $this->getDoctrine()->getManager();
        
        $idCompania = $this->getUser()->getIdCompania();
        $procesosxauditorias = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria);
        $auditoresxproceso = $bd->getRepository('App\Entity\Calidad\auditoresxproceso')->findBy(array('idProcesoxauditoria' => $idProcesoxAuditoria));

        $idAuditoria = $procesosxauditorias->getIdAuditoria();
        $auditoria = $bd->getRepository('App\Entity\Calidad\auditorias')->find($idAuditoria);

        $todosconceptosauditoria = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->findAll();

        foreach ($todosconceptosauditoria as $concepauditoria) {
            $vectoralAuditoriasxconceptos[] = $concepauditoria->getProcesosxauditoriasxConceptosauditoria()->getIdAuditoria();
            $vectoralProcesosxconceptos[] = $concepauditoria->getProcesosxauditoriasxConceptosauditoria()->getIdProceso();
            $vectoralSubProcesosxconceptos[] = $concepauditoria->getProcesosxauditoriasxConceptosauditoria()->getIdSubproceso();
        }

        $proceso = $procesosxauditorias->getIdProceso();
        $subproceso = $procesosxauditorias->getIdSubproceso();

        if (!$subproceso) {
            $subproceso = 0;
        }

        $conceptosauditoria = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->findBy(array('idProcesoxauditoria' => $idProcesoxAuditoria));
        if (empty($vectoralAuditoriasxconceptos)) {
            $vectoralAuditoriasxconceptos = 0;
        }
        if (empty($vectoralProcesosxconceptos)) {
            $vectoralProcesosxconceptos = 0;
        }
        if (empty($vectoralSubProcesosxconceptos)) {
            $vectoralSubProcesosxconceptos = 0;
        }
        $conceptosauditoria2 = new conceptosauditoria();
         $Caracterizacion=$bd->getRepository('App\Entity\Calidad\caracterizacion')->findBy(array('idProceso' => $proceso,'estado'=>1));
        $arrayReq=[];
        if(count($Caracterizacion)>0){
            $idCaracterizacion=$Caracterizacion[0]->getId();
            $requisitos=$bd->getRepository('App\Entity\Calidad\\requisitosxProceso')->findBy(array('idCaracterizacion' => $idCaracterizacion));
            foreach ($requisitos as $value) {
               $arrayReq[]=$value->getRequisito();
            }
         
        }
                
        $dql1="select a from App\Entity\Calidad\\requisitos a WHERE a.id IN (:arrayReq) ";
        $query1 = $bd->createQuery($dql1);
        $query1->setParameter('arrayReq', $arrayReq);
        $arrayRequisitos = $query1->getResult();
        
        $FORMA = $this->createFormBuilder($conceptosauditoria2, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('concepto', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Concepto'))
//                ->add('requisitosxConceptos', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
//                    'class' => 'App\Entity\Calidad\\requisitos',
//                    'choice_value' => 'id',
//                    'choice_label' => 'requisito',
//                    'label' => 'Requisitos',
//                    'placeholder'=>'Seleccione Requisito',
//                    'required'=>false,
//                    'multiple'=>true,
//                     'attr' => array(
//                        'class' => 'form-control selectpicker',
//                            'data-live-search' => true,
//                    ), 
//                    'query_builder' => function(\App\Repository\Calidad\requisitosRepository $er ) use ($arrayRequisitos) {
//                        return $er->createQueryBuilder('w')
//                                ->Where('w.id in (:B)')
//                                ->setParameter('B', $arrayRequisitos)
//                                ->orderBy('w.id', 'ASC');
//                    },
//                    ))
                ->add('campo1', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\\requisitos',
                    'choice_value' => 'id',
                    'choice_label' => 'requisito',
                   // 'label' => 'Requisitos',
                   // 'placeholder'=>'Seleccione Requisito',
                   // 'required'=>false,
                    'multiple'=>true,
                    // 'attr' => array(
                    //    'class' => 'Selectpicker',
                        //'data-live-search' => true,
                    //), 
                    'query_builder' => function(\App\Repository\Calidad\requisitosRepository $er ) use ($arrayRequisitos) {
                        return $er->createQueryBuilder('w')
                                ->Where('w.id in (:B)')
                                ->setParameter('B', $arrayRequisitos)
                                ->orderBy('w.id', 'ASC');
                    },
                    ))               
             //  ->add('campo1', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label'=>'prueba'))                 
                ->add('critico', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, array('label' => 'Critico'))
                ->add('guardar3', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => '+'))
                ->getForm();

        $FORMA1 = $this->createFormBuilder($conceptosauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_traerprocesos', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('SelAuditoria', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\auditorias',
                    'choice_value' => 'id',
                    'choice_label' => 'nombre',
                    'label' => 'Auditorias',
                    'placeholder' => 'Seleccione una auditoria',
                    'data' => $bd->getRepository("App\Entity\Calidad\auditorias")->find($idAuditoria),
                    'query_builder' => function(\App\Repository\Calidad\auditoriasRepository $er ) use ($idCompania, $vectoralAuditoriasxconceptos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->andWhere('w.id in (:B)')
                                ->setParameter('a', $idCompania)
                                ->setParameter('B', $vectoralAuditoriasxconceptos)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('SelProceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\procesos',
                    'choice_value' => 'id',
                    'choice_label' => 'proceso',
                    'label' => 'Procesos',
                    'placeholder' => 'Seleccione un Proceso',
                    'data' => $bd->getRepository("App\Entity\Calidad\procesos")->find($proceso),
                    'query_builder' => function(\App\Repository\Calidad\procesosRepository $er ) use ( $idCompania, $vectoralProcesosxconceptos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->andWhere('w.id in (:B)')
                                ->setParameter('a', $idCompania)
                                ->setParameter('B', $vectoralProcesosxconceptos)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('subproceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\subprocesos',
                    'choice_value' => 'id',
                    'choice_label' => 'subproceso',
                    'label' => 'SubProcesos',
                    'query_builder' => function(\App\Repository\Calidad\subprocesosRepository $er ) use ( $proceso, $vectoralSubProcesosxconceptos ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idProceso = :a')
                                ->andWhere('w.id in (:B)')
                                ->setParameter('a', $proceso)
                                ->setParameter('B', $vectoralSubProcesosxconceptos)
                                ->orderBy('w.id', 'ASC');
                    },
                    'data' => $bd->getRepository("App\Entity\Calidad\subprocesos")->find($subproceso)
                ))
                ->add('guardar2', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class)
                ->getForm();

        $FORMA2 = $this->createFormBuilder($conceptosauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_guardar_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('archivo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, array('label' => 'Subir archivo plano'))
                ->add('guardar1', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
        $valores = $request->request->get('form');
        $valores2 = $request->files->get('form');
    
        if (!empty($valores['concepto'])) {

            $FORMA->handleRequest($request);
        }
        if (!empty($valores['SelAuditoria'])) {

            $FORMA1->handleRequest($request);
        }
        if (!empty($valores2['archivo'])) {
            $FORMA2->handleRequest($request);
        }
      
        if ($FORMA->isSubmitted() && $FORMA->isValid()) {
                                
            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(3));
            $conceptosauditoria2->setProcesosxauditoriasxConceptosauditoria($bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria));
           
            $arrayConc = $conceptosauditoria2->getCampo1()->toArray();
            $bd->persist($conceptosauditoria2);
            $idConc=$conceptosauditoria2->getId();
            $bd->flush();
            foreach ($arrayConc as $Conc) {
               // dump($Conc->getId()); exit;
                $req = new requisitosxconceptosauditoria();
                $req->setConceptosAuditoriaxRequisitos($bd->getRepository('App\Entity\Calidad\conceptosauditoria')->find($idConc));
                $req->setRequisitosxRequisitosConceptos($bd->getRepository('App\Entity\Calidad\\requisitos')->find($Conc->getId()));
               $bd->persist($req);
                $bd->flush();
            }
            
            return $this->redirectToRoute('auditoria_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria));
        } else if ($FORMA1->isSubmitted() && $FORMA1->isValid()) {

            $dql = "select a from App\Entity\Calidad\procesosxauditoria a where a.idProceso=:X "
                    . " and a.idAuditoria=:Y and a.idSubproceso=:Z";
            $query = $bd->createQuery($dql);
            $query->setParameter(':X', $valores['SelProceso']);
            $query->setParameter(':Y', $valores['SelAuditoria']);
            $query->setParameter(':Z', $valores['subproceso']);
            $record = $query->getResult();
            foreach ($record as $re) {
                $conceptosextraidos = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->findBy(array('idProcesoxauditoria' => $re->getId()));
            }
            if(!empty($conceptosextraidos)){
                
                foreach ($conceptosextraidos as $concep) {
                    $conceptosauditoria2 = new conceptosauditoria();
                    $conceptosauditoria2 -> setProcesosxauditoriasxConceptosauditoria($bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria));
                    $conceptosauditoria2 -> setConcepto($concep->getConcepto());
                    $conceptosauditoria2 -> setCritico($concep->getCritico());
                    $bd->persist($conceptosauditoria2);
                    $bd->flush();
                }
            }

            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(3));

            $bd->flush();
  
            return $this->redirectToRoute('auditoria_planejecucion',
                    array('idProcesoxAuditoria' => $idProcesoxAuditoria));
        } elseif ($FORMA2->isSubmitted() && $FORMA2->isValid()) {
            $file = $FORMA2['archivo']->getData();
            if($file->getMimeType()=="text/plain" || $file->getMimeType()=="application/excel"){
                
            $archivo = file_get_contents($file->getPathname());
          
            $cadena = explode("\r\n", $archivo);
            $band = 0;
            $noinserta = 0;
            $error = "";
            
            foreach ($cadena as $cad) {
                if ($cad != "") {
                    $band++;
                    $cadena2 = explode(";", $cad);
                    $CriticoMayus = strtoupper(trim($cadena2[1]));

                    if ($CriticoMayus == 'SI') {
                        $critico = 1;
                    } elseif ($CriticoMayus == 'NO') {
                        $critico = 0;
                    } else {
                        $error = "Error de cargue en el archivo: Por favor revisar la linea " . $band;
                        $noinserta = 1;
                        break;
                    }

                    $conceptosauditoria2 = new conceptosauditoria();
                    $conceptosauditoria2->setProcesosxauditoriasxConceptosauditoria($bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria));
                    $conceptosauditoria2->setConcepto(trim($cadena2[0]));
                    $conceptosauditoria2->setCritico(($critico));
                    $bd->persist($conceptosauditoria2);
          
                }
            }
            $auditoria->setEstadosauditoriaxAuditorias($bd->getRepository('App\Entity\Calidad\estados_auditoria')->find(3));

            if ($noinserta == 0) {
                $bd->flush();
            } else {
                $this->addFlash('error', $error);
            }
            }else{
                
                $this->addFlash('error', 'Tipo de archivo incorrecto!');
                
            }
 
            return $this->redirectToRoute('auditoria_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria));

        }
        
        return $this->render('Calidad\auditoria\planAuditoria.html.twig', array('procesoxauditoria' => $procesosxauditorias,
                    'auditoresxproceso' => $auditoresxproceso,
                    'conceptosauditoria' => $conceptosauditoria,
                    'requisitos'=>$arrayRequisitos,
                    'form' => $FORMA->createView(),
                    'form1' => $FORMA1->createView(),
                    'form2' => $FORMA2->createView()
        ));
    }

    public function eliminarconceptoAuditoria($idConceptoxAuditoria) {
        $bd = $this->getDoctrine()->getManager();
        $conceptoxauditoria = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->find($idConceptoxAuditoria);
        $idProcesoxAuditoria = $conceptoxauditoria->getProcesosxauditoriasxConceptosauditoria()->getId();
        
        try {
            $bd->remove($conceptoxauditoria);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
        return $this->redirectToRoute('auditoria_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria));
    }
    
    public function eliminarRequisitoConceptoAuditoria($idRequisitoxConcepto){
       // echo $idRequisitoxConcepto;exit;
        $bd = $this->getDoctrine()->getManager();
        $requisitoxconcepto = $bd->getRepository('App\Entity\Calidad\\requisitosxconceptosauditoria')->find($idRequisitoxConcepto);
        $idConcepto=$requisitoxconcepto->getIdConcepto();
        $conceptoxauditoria = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->find($idConcepto);
        $idProcesoxAuditoria = $conceptoxauditoria->getProcesosxauditoriasxConceptosauditoria()->getId();
        $bd->remove($requisitoxconcepto);
        $bd->flush();
        $requisitosxconcepto = $bd->getRepository('App\Entity\Calidad\\requisitosxconceptosauditoria')->findby(array('idConcepto'=>$idConcepto));
        
        if (count($requisitosxconcepto)==0){
            $bd->remove($conceptoxauditoria);
            $bd->flush();
        }
        
        return $this->redirectToRoute('auditoria_planejecucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria));
        
    }

    public function asignarAuditoria() {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $fechai = new \DateTime();
        $fechaini = $fechai->format('Y-m-d');

        $fechaf = new \DateTime();
        $fechafin = $fechaf->format('Y-m-d');

        $auditoriaDql = "select a from App\Entity\Calidad\auditorias a WHERE a.fecha"
                . " between '$fechaini 00:00:00' and '$fechafin 23:59:59' AND a.idCompania=:Compania order by a.id ";
        $query = $bd->createQuery($auditoriaDql);
        $query->setParameter(':Compania', $idCompania);
        $auditoria = $query->getResult();

        return $this->render('Calidad\auditoria\verAuditoriaAsignada.html.twig', array('auditoria' => $auditoria
        ));
    }

    public function ejecutarAuditoria($idAuditoria, $idProceso, $idSubproceso, Request $request, $idConcepto, $filtroConcepto) {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $band = 0;
        if ($idConcepto == 0) {
            $ejecucion = new ejecucionauditoria();
        } else {
            $ejecu = $bd->getRepository('App\Entity\Calidad\ejecucionauditoria')->findBy(array('idConceptoauditoria' => $idConcepto));

            if (!empty($ejecu)) {
                foreach ($ejecu as $eje) {
                    $idEje = $eje->getId();
                    break;
                }
                $ejecucion = $bd->getRepository('App\Entity\Calidad\ejecucionauditoria')->find($idEje);
            }
        }

        if (empty($ejecucion)) {
            $ejecucion = new ejecucionauditoria();
            $band = 1;
        }

        $valores = $request->request->get('form');

        $procesosxauditoria = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->findBy(
                array('idAuditoria' => $idAuditoria, 'idProceso' => $idProceso, 'idSubproceso' => $idSubproceso));


        if (empty($procesosxauditoria)) {
            $procesosxauditoria = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->findBy(
                    array('idAuditoria' => $idAuditoria, 'idProceso' => $idProceso));
        }
        foreach ($procesosxauditoria as $proceaui) {
            $idProcesoAuditoria = $proceaui->getId();
        }

        if (!empty($valores['filtroConceptos']) || $filtroConcepto != "") {
            if ($filtroConcepto == 0) {
                $filtroConcepto = $valores['filtroConceptos'];
            }
            if ($valores['filtroConceptos'] == 1 || $filtroConcepto == 1) {
                $auditoriaDql = "select  a from App\Entity\Calidad\conceptosauditoria a "
                        . "  LEFT JOIN a.ejecucionauditoriaxConceptosauditoria b "
                        . " where a.idProcesoxauditoria =:IdprocesoAu and "
                        . "   b.id is null order by a.id";
            } else {
                $auditoriaDql = "select a from App\Entity\Calidad\conceptosauditoria a "
                        . "  JOIN a.ejecucionauditoriaxConceptosauditoria b "
                        . " where a.idProcesoxauditoria =:IdprocesoAu and "
                        . " b.id is not null order by a.id";
            }
        } else {
            $auditoriaDql = "select  a from App\Entity\Calidad\conceptosauditoria a "
                    . "  LEFT JOIN a.ejecucionauditoriaxConceptosauditoria b "
                    . " where a.idProcesoxauditoria =:IdprocesoAu and "
                    . "  b.id is null order by a.id";
        }
        $query = $bd->createQuery($auditoriaDql);
        $query->setParameter(':IdprocesoAu', $idProcesoAuditoria);

        $auditoriaejecucion = $query->getResult();
        $FORMA = $this->createFormBuilder($auditoriaejecucion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_ejecutar', array('idAuditoria' => $idAuditoria, 'idProceso' => $idProceso, 'idSubproceso' => $idSubproceso))))
                ->add('filtroConceptos', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'label' => 'Ver',
                    'choices' => array(
                        'Conceptos Pendientes' => 1,
                        'Conceptos Auditados' => 2,
                    ),
                    'data' => $filtroConcepto
                ))
                ->add('filtrar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Filtrar'))
                ->getForm();

        $FORMA1 = $this->createFormBuilder($ejecucion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_ejecutar', array('idAuditoria' => $idAuditoria, 'idProceso' => $idProceso, 'idSubproceso' => $idSubproceso, 'idConcepto' => $idConcepto))))
                ->add('cumple', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'label' => 'Cumple',
                    'choices' => array(
                        'Si' => 1,
                        'No' => 0,
                    )
                ))
                ->add('observacion', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Observacion'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Filtrar'))
                ->getForm();

        $FORMA1->handleRequest($request);

        if ($request->request->get('filtroConcepto') != "") {
            $filtroConcepto = $request->request->get('filtroConcepto');
        }
        
        if ($FORMA1->isSubmitted() && $FORMA1->isValid()) {
            if ($band == 1) {
                $ejecucion->setFechacrea(new \DateTime('now'));
                $ejecucion->setUsuariosxEjecucionauditoria($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
                $ejecucion->setConceptosauditoriaxEjecucionauditoria($bd->getRepository('App\Entity\Calidad\conceptosauditoria')->find($request->request->get('conceptoid')));
                $bd->persist($ejecucion);
                $bd->flush();
            } else {
                $ejecucion->setFechacrea(new \DateTime('now'));
                $ejecucion->setUsuariosxEjecucionauditoria($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
                $ejecucion->setConceptosauditoriaxEjecucionauditoria($bd->getRepository('App\Entity\Calidad\conceptosauditoria')->find($request->request->get('conceptoid')));
                $bd->flush();
            }
            if (empty($valores['filtroConceptos'])) {
                $valores['filtroConceptos'] = $filtroConcepto;
            }
            if ($request->request->get('generar') == 1) {

                return $this->redirectToRoute('noconforme_registrar', array(
                            'idConceptoAuditoria' => $idConcepto
                ));
            } else if ($request->request->get('generar') == 2) {
                return $this->redirectToRoute('acciones_mejora_nuevo', array(
                            'idConceptoAuditoria' => $idConcepto
                ));
            } else {
                return $this->redirectToRoute('auditoria_ejecutar', array(
                            'idAuditoria' => $idAuditoria,
                            'idProceso' => $idProceso,
                            'idSubproceso' => $idSubproceso,
                            'idConcepto' => 0,
                            'filtroConcepto' => $valores['filtroConceptos'],
                            'ejecucion' => $ejecucion
                ));
            }
        }
        
        if (empty($valores['filtroConceptos'])) {
            $valores['filtroConceptos'] = $filtroConcepto;
        }

        if (!empty($ejecucion)) {
            $ejecucion = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->find($idConcepto);
        }

        return $this->render('Calidad\auditoria\ejecutarAuditoria.html.twig', array(
                    'form' => $FORMA->createView(),
                    'form1' => $FORMA1->createView(),
                    'auditoriaejecucion' => $auditoriaejecucion,
                    'procesosxauditoria' => $procesosxauditoria,
                    'idConcepto' => $idConcepto,
                    'filtroConcepto' => $valores['filtroConceptos'],
                    'ejecucion' => $ejecucion
        ));
    }

    public function ejecucionAuditoria($idProcesoxAuditoria, Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $valores = $request->request->get('form');

        if (!empty($valores['auditor'])) {
            $auditoriaDql = "select  a from App\Entity\Calidad\procesosxauditoria a "
                    . " left JOIN a.conceptosauditoriaxProcesosxauditorias b"
                    . "  left JOIN b.ejecucionauditoriaxConceptosauditoria c"
                    . " where a.id=:IdProceso and  c.idAuditorproceso =:Auditor order by a.id";
            $query = $bd->createQuery($auditoriaDql);
            $query->setParameter(':Auditor', $valores['auditor']);
            $query->setParameter(':IdProceso', $idProcesoxAuditoria);
        } else {
//            $procesosxauditoria = $bd->getRepository('App\Entity\Calidad\procesosxauditoria')->find($idProcesoxAuditoria);

            $auditoriaDql = "select  a from App\Entity\Calidad\procesosxauditoria a "
                    . "  left JOIN a.conceptosauditoriaxProcesosxauditorias b"
                    . " left  JOIN b.ejecucionauditoriaxConceptosauditoria c "
                    . " where a.id=:IdProceso order by a.id";
            $query = $bd->createQuery($auditoriaDql);
            $query->setParameter(':IdProceso', $idProcesoxAuditoria);
        }

        $procesosxauditoria = $query->getResult();

        foreach ($procesosxauditoria as $proceAuditoria) {
            $idProcesoAuditoria = $proceAuditoria->getId();
        }
        if (empty($idProcesoAuditoria)) {
            foreach ($procesosxauditoria as $proxaudi) {
                $idProcesoAuditoria = $proxaudi->getId();
            }
        }
        $conceptos = $bd->getRepository('App\Entity\Calidad\conceptosauditoria')->findBy(array('idProcesoxauditoria' => $idProcesoAuditoria));

        foreach ($conceptos as $ejecucion) {
            foreach ($ejecucion->getEjecucionauditoriaxConceptosauditoria() as $idEjec) {
                $VectorAuditorEjecucion[] = $idEjec->getIdAuditorproceso();
            }
        }

        if (empty($VectorAuditorEjecucion)) {
            $VectorAuditorEjecucion[] = 0;
        }
        if (empty($valores['auditor'])) {
            $valores['auditor'] = 0;
        }

        $FORMA = $this->createFormBuilder($procesosxauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_ejeucion', array('idProcesoxAuditoria' => $idProcesoxAuditoria))))
                ->add('auditor', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Central\usuarios',
                    'choice_value' => 'id',
                    'choice_label' => 'nombre',
                    'label' => 'Auditor',
                    'query_builder' => function(\App\Repository\Central\usuariosRepository $er ) use ($VectorAuditorEjecucion) {
                        return $er->createQueryBuilder('w')
                                ->Where('w.id IN (:B)')
                                ->setParameter('B', $VectorAuditorEjecucion)
                                ->orderBy('w.id', 'ASC');
                    },
                    'data' => $bd->getRepository("App\Entity\Central\usuarios")->find($valores['auditor']),
                ))->add('filtrar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Filtrar'))
                ->getForm();
//        
        $auditorp = $bd->getRepository('App\Entity\Central\usuarios')->findBy(array('id' => $valores['auditor']));

        if (empty($auditorp)) {
            $auditoriaDql = "select  a from App\Entity\Central\usuarios a  "
                    . " where a.id in (:VectorAuditores) order by a.id";
            $query = $bd->createQuery($auditoriaDql);
            $query->setParameter(':VectorAuditores', $VectorAuditorEjecucion);
            $auditorp = $query->getResult();
        }

        return $this->render('Calidad\auditoria\verEjecucion.html.twig', array('procesosauditoria' => $procesosxauditoria,
                    'form' => $FORMA->createView(),
                    'idAuditor' => $valores['auditor'],
                    'auditor' => $auditorp
        ));
    }
    public function listartiposAuditoria(Request $request, PaginatorInterface $paginator){
        $bd = $this->getDoctrine()->getManager();
        $idCompania=$this->getUser()->getIdCompania();
        $tiposauditoria= $bd->getRepository('App\Entity\Calidad\\tiposauditoria')->findBy(['idCompania'=>$idCompania]);
                
        $pagination = $paginator->paginate($tiposauditoria, $request->query->getInt('page', 1), 15);
       
        return $this->render('Calidad\auditoria\listarTipoAuditoria.html.twig',
                array('reg' => $pagination
        ));
    }
    public function nuevotiposAuditoria(){
        $bd = $this->getDoctrine()->getManager();
        $idCompania=$this->getUser()->getIdCompania();
        $tiposauditoria= new tiposauditoria();
        
        $FORMA = $this->createFormBuilder($tiposauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_tipos_guardar')))
                ->add('tipoauditoria', \Symfony\Component\Form\Extension\Core\Type\TextType::class,array('label'=>'Tipo de auditoria'))
                ->add('idCompania', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, array('data'=>$idCompania))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
        
        return $this->render('Calidad\auditoria\nuevoTipoAuditoria.html.twig',
                array('form' => $FORMA->createView()));
        
    }
    
    public function guardartiposAuditoria(Request $request){
        $bd = $this->getDoctrine()->getManager();
        $idCompania=$this->getUser()->getIdCompania();
        $tiposauditoria= new tiposauditoria();
        
        $FORMA = $this->createFormBuilder($tiposauditoria, array(
                    'method' => 'POST', 'action' => $this->generateUrl('auditoria_tipos_guardar')))
                ->add('tipoauditoria', \Symfony\Component\Form\Extension\Core\Type\TextType::class,array('label'=>'Tipo de auditoria'))
                ->add('idCompania', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, array('data'=>$idCompania))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
        $FORMA->handleRequest($request);
       
        if($FORMA->isSubmitted() && $FORMA->isValid()){
            $bd->persist($tiposauditoria);
            $bd->flush();
            return $this->redirectToRoute('auditoria_tipos_listar');
        }
        
        return $this->render('Calidad\auditoria\nuevoTipoAuditoria.html.twig',
                array('form' => $FORMA->createView()
        ));
    }
    
    public function editartiposAuditoria($id){
        $bd = $this->getDoctrine()->getManager();
        $tiposauditoria= $bd->getRepository('App\Entity\Calidad\\tiposauditoria')->find($id);
        
        $FORMA = $this->createFormBuilder($tiposauditoria, array(
                    'method' => 'PUT', 'action' => $this->generateUrl('auditoria_tipos_actualizar',array('id'=>$id))))
                ->add('tipoauditoria', \Symfony\Component\Form\Extension\Core\Type\TextType::class,array('label'=>'Tipo de auditoria'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
        
        return $this->render('Calidad\auditoria\nuevoTipoAuditoria.html.twig',
                array('form' => $FORMA->createView()
        ));
    }
    public function actualizartiposAuditoria(Request $request,$id){
        $bd = $this->getDoctrine()->getManager();
        $tiposauditoria= $bd->getRepository('App\Entity\Calidad\\tiposauditoria')->find($id);
        
        $FORMA = $this->createFormBuilder($tiposauditoria, array(
                    'method' => 'PUT', 'action' => $this->generateUrl('auditoria_tipos_actualizar',array('id'=>$id))))
                ->add('tipoauditoria', \Symfony\Component\Form\Extension\Core\Type\TextType::class,array('label'=>'Tipo de auditoria'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();
        $FORMA->handleRequest($request);
       
        if($FORMA->isSubmitted() && $FORMA->isValid()){
            
            $bd->flush();
            return $this->redirectToRoute('auditoria_tipos_listar');
        }
        
        return $this->render('Calidad\auditoria\nuevoTipoAuditoria.html.twig',
                array('form' => $FORMA->createView()
        ));
    }
    
     public function eliminartiposAuditoria($id) {

        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository('App\Entity\Calidad\\tiposauditoria')->find($id);
        
         try {
            $bd->remove($record);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
      
        return $this->redirectToRoute('auditoria_tipos_listar');
    }
}
