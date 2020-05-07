<?php

namespace App\Controller\Calidad;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Calidad\caracterizacion;
use App\Form\Calidad\caracterizacionType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Calidad\actividadesxproceso;
use App\Entity\Nomina\cargos;
use App\Entity\Calidad\responsablesxprocesos;
use App\Entity\Calidad\requisitosxproceso;
use Doctrine\ORM\EntityRepository;
use App\Entity\Calidad\documentosexternos;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Calidad\procesos;
use Knp\Component\Pager\PaginatorInterface;

class caracterizacionController extends AbstractController {

    public function verCaracterizacion($idProceso, $id) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $registro = $bd->getRepository('App\Entity\Calidad\caracterizacion')->findBy(array('idProceso' => $idProceso, 'estado' => array(1, 2)));
        // $registro = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find(100));
        if (!empty($registro)) {
            foreach ($registro as $dato) {
                $id_caracterizacion = $dato->getId();
            }

            $responsablesxprocesos = $bd->getRepository('App\Entity\Calidad\\responsablesxprocesos')
                    ->findBy(array('idCaracterizacion' => $id_caracterizacion));

            $requisitosxproceso = $bd->getRepository('App\Entity\Calidad\\requisitosxproceso')
                    ->findBy(array('idCaracterizacion' => $id_caracterizacion));

            $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($id_caracterizacion);


            $FORMA3 = $this->createFormBuilder($caracterizacion, array(
                        'method' => 'POST', 'action' => $this->generateUrl('caracterizacion_obsoleto', array('idCaracterizacion' => $id_caracterizacion))))
                    ->add('notaobsoleto', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de obsolecencia'))
                    ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                    ->getForm();

            $FORMA4 = $this->createFormBuilder($caracterizacion, array(
                        'method' => 'POST', 'action' => $this->generateUrl('caracterizacion_publicar', array('idCaracterizacion' => $id_caracterizacion))))
                    ->add('nota_publica', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Publicacion'))
                    ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                    ->getForm();

            $FORMA5 = $this->createFormBuilder($caracterizacion, array(
                        'method' => 'POST', 'action' => $this->generateUrl('caracterizacion_dardebaja', array('idCaracterizacion' => $id_caracterizacion))))
                    ->add('nota_baja', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Baja'))
                    ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                    ->getForm();

            $varForm3 = $FORMA3->createView();
            $varForm4 = $FORMA4->createView();
            $varForm5 = $FORMA5->createView();
        } else {
            $responsablesxprocesos = "";
            $requisitosxproceso = "";
            $caracterizacion = "";
            $varForm3 = "";
            $varForm4 = "";
            $varForm5 = "";
        }

        $procesos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
        $rolesxprocesos = $bd->getRepository('App\Entity\Calidad\\rolesxproceso')->findBy(array('idProceso' => $idProceso));
        $registroAc = $bd->getRepository('App\Entity\Calidad\actividadesxproceso')->findBy(array('idProceso' => $idProceso), array('id' => 'asc'));
        $archivos = $bd->getRepository('App\Entity\Calidad\documentosexternos')->findBy(array('idProceso' => $idProceso), array('id' => 'asc'));

        $documentosexternos = new documentosexternos();

        if ($id == 0) {
            $actividadesxproceso = new actividadesxproceso();
        } else {
            $actividadesxproceso = $bd->getRepository('App\Entity\Calidad\actividadesxproceso')->find($id);
        }

        $FORMA = $this->createFormBuilder($actividadesxproceso, array(
                    'method' => 'POST', 'action' => $this->generateUrl('actividadesxproceso_guardar', array('idProceso' => $idProceso, 'id' => $id))))
                ->add('proveedor', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Proveedor'))
                ->add('entradas', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Entradas'))
                ->add('salidas', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Salidas'))
                ->add('descripcion', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Descripción'))
                ->add('proceso', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array(
                        'Planear' => '1',
                        'Hacer' => '2',
                        'Verificar' => '3',
                        'Actuar' => '4',),))
                ->add('cliente', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Cliente'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA2 = $this->createFormBuilder($documentosexternos, array(
                    'method' => 'POST', 'action' => $this->generateUrl('documentosexternos_guardar', array('idProceso' => $idProceso))))
                ->add('tiporeqnormaxDocumentosexternos', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\\tipo_req_norma',
                    'choice_value' => 'id',
                    'choice_label' => 'reqNorma',
                    'label' => 'Tipo req norma',
                    'query_builder' => function(\App\Repository\Calidad\tipo_req_normaRepository $er) use ($idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->setParameter('a', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nombre'))
                ->add('archivo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, array('data_class' => null, 'required' => false))
                ->add('enlace', \Symfony\Component\Form\Extension\Core\Type\UrlType::class, array('label' => 'Enlace externo', 'required' => false))
                ->add('observaciones', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Observación', 'required' => false))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);

        return $this->render('Calidad\caracterizacion\verCaracterizacion.html.twig', array('form' => $FORMA->createView(),
                    'form2' => $FORMA2->createView(),
                    'form3' => $varForm3,
                    'form4' => $varForm4,
                    'form5' => $varForm5,
                    'registro' => $registro,
                    'idProceso' => $idProceso, 'responsablesxprocesos' => $responsablesxprocesos, 'requisitosxproceso' => $requisitosxproceso,
                    'procesos' => $procesos, 'rolesxproceso' => $rolesxprocesos, 'registroAc' => $registroAc,
                    'archivos' => $archivos, 'permisos' => $permisos));
    }

    public function guardarDocumentosExternos(Request $request, $idProceso) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();

        $documentosexternos = new documentosexternos();

        $FORMA2 = $this->createFormBuilder($documentosexternos, array(
                    'method' => 'POST', 'action' => $this->generateUrl('documentosexternos_guardar', array('idProceso' => $idProceso))))
                ->add('tiporeqnormaxDocumentosexternos', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\\tipo_req_norma',
                    'choice_value' => 'id',
                    'choice_label' => 'reqNorma',
                    'label' => 'Tipo req norma',
                    'query_builder' => function(\App\Repository\Calidad\tipo_req_normaRepository $er) use ($idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->setParameter('a', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nombre'))
                ->add('archivo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, array('data_class' => null, 'required' => false))
                ->add('enlace', \Symfony\Component\Form\Extension\Core\Type\UrlType::class, array('label' => 'Enlace externo', 'required' => false))
                ->add('observaciones', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Observación', 'required' => false))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA2->handleRequest($request);
        if ($FORMA2->isSubmitted() && $FORMA2->isValid()) {

            $procesosxDocumentosExternos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
            $documentosexternos->setProcesosxDocumentosExternos($procesosxDocumentosExternos);
            $documentosexternos->setFechaCrea(new \DateTime('now'));
            $usuariosxDocumentosExternos = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $documentosexternos->setUsuariosxDocumentosExternos($usuariosxDocumentosExternos);

            $Archivo = $documentosexternos->getArchivo();

            if ($Archivo) {
                $documentos = $this->getDoctrine()->getManager('documentos');
                $anexo = new \App\Entity\Documentos\Documentos();
                $anexo->setDocumento(base64_encode(file_get_contents($Archivo)));
                $anexo->setExt($Archivo->guessExtension());
                $documentos->persist($anexo);
                $documentos->flush();
                $idAnexo = $anexo->getId();
                $documentosexternos->setArchivo($idAnexo);
            }


            $bd->persist($documentosexternos);

            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            $bd->flush();
            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }

        return $this->render('Calidad\caracterizacion\verCaracterizacion.html.twig', array('form2' => $FORMA2->createView()));
    }

    public function eliminarDocumentosExternos($id, $idProceso) {

        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository('App\Entity\Calidad\documentosexternos')->find($id);
        try {
            $bd->remove($record);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
        return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
    }

    public function publicarCaracterizacion($idCaracterizacion, Request $request) {

        $bd = $this->getDoctrine()->getManager();
        $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($idCaracterizacion);
        $idProceso = $caracterizacion->getIdProceso();

        $FORMA4 = $this->createFormBuilder($caracterizacion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('caracterizacion_publicar', array('idCaracterizacion' => $idCaracterizacion))))
                ->add('nota_publica', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Publicacion'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA4->handleRequest($request);

        if ($FORMA4->isValid()) {

            $caracterizacion->setEstado(2);
            $caracterizacion->setFechaPublica(new \DateTime('now'));
            $usuariospublicaxCaracterizacion = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $caracterizacion->setUsuariospublicaxCaracterizacion($usuariospublicaxCaracterizacion);
            $bd->flush();

            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }

        return $this->render('Calidad\caracterizacion\verCaracterizacion.html.twig',
                        array('form4' => $FORMA4->createView()));
    }

    public function guardarActividadesxProceso(Request $request, $idProceso, $id) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        if ($id == 0) {
            $actividadesxproceso = new actividadesxproceso();
        } else {
            $actividadesxproceso = $bd->getRepository('App\Entity\Calidad\actividadesxproceso')->find($id);
        }

        $FORMA = $this->createFormBuilder($actividadesxproceso, array(
                    'method' => 'POST', 'action' => $this->generateUrl('actividadesxproceso_guardar', array('idProceso' => $idProceso, 'id' => $id))))
                ->add('proveedor', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Proveedor'))
                ->add('entradas', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Entradas'))
                ->add('salidas', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Salidas'))
                ->add('descripcion', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Descripción'))
                ->add('proceso', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array(
                        'Planear' => '1',
                        'Hacer' => '2',
                        'Verificar' => '3',
                        'Actuar' => '4',),))
                ->add('cliente', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Cliente'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA->handleRequest($request);

        if ($FORMA->isSubmitted() && $FORMA->isValid()) {

            if ($id == 0) {
                $procesosxActividaesxProceso = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
                $actividadesxproceso->setProcesosxActividaesxProceso($procesosxActividaesxProceso);
                $actividadesxproceso->setFechaCrea(new \DateTime('now'));
                $usuariosxActividadesxProcesoCrea = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
                $actividadesxproceso->setUsuariosxActividadesxProcesoCrea($usuariosxActividadesxProcesoCrea);
                $bd->persist($actividadesxproceso);
            } else {
                $actividadesxproceso->setFechaMod(new \DateTime('now'));
                $usuariosxActividadesxProcesoMod = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
                $actividadesxproceso->setUsuariosxActividadesxProcesoMod($usuariosxActividadesxProcesoMod);
            }

            $actividadesxproceso->setIdCompania($idCompania);

            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            $bd->flush();

            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }

        return $this->render('Calidad\caracterizacion\verCaracterizacion.html.twig',
                        array('form' => $FORMA->createView()));
    }

    public function eliminarActividadesxProceso($id, $idProceso) {
        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository('App\Entity\Calidad\actividadesxproceso')->find($id);
        try {
            $bd->remove($record);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }
        return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
    }

    public function nuevaCaracterizacion($idProceso) {

        $caracterizacion = new caracterizacion();
        $idCompania = $this->getUser()->getIdCompania();
        $bd = $this->getDoctrine()->getManager();
        $procesos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);

        $FORMA = $this->createForm(caracterizacionType::class, $caracterizacion, array(
            'compania' => $idCompania,
            'method' => 'POST',
            'action' => $this->generateUrl('caracterizacion_guardar', array('idProceso' => $idProceso)),
            'Procesos' => $procesos,
        ));

        return $this->render('Calidad\caracterizacion\nuevaCaracterizacion.html.twig', array('form' => $FORMA->createView(), 'idProceso' => $idProceso));
    }

    public function cargarResponsablesRequisitos($idProceso) {
        $bd = $this->getDoctrine()->getManager();
        $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->findBy(array('idProceso' => $idProceso, 'estado' => array(1, 2)));
        foreach ($caracterizacion as $dato) {
            $id_caracterizacion = $dato->getId();
            break;
        }
        $record = $bd->getRepository('App\Entity\Calidad\\responsablesxprocesos')->findBy(array('idCaracterizacion' => $id_caracterizacion));
        foreach ($record as $responsable) {
            $id_responsable[] = $responsable->getResponsables();
        }
        $record2 = $bd->getRepository('App\Entity\Calidad\\requisitosxproceso')->findBy(array('idCaracterizacion' => $id_caracterizacion));
        foreach ($record2 as $requisitos) {
            $id_requisitos[] = $requisitos->getRequisito();
        }
        if (empty($id_requisitos)) {
            $id_requisitos = "";
        }
        if (empty($id_responsable)) {
            $id_responsable = "";
        }
        $arrData = ['responsables' => $id_responsable, 'requisitos' => $id_requisitos];
        return new JsonResponse($arrData);
    }

    public function guardarCaracterizacion(Request $request, $idProceso) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();

        $caracterizacion = new caracterizacion();
        $procesos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
        $FORMA = $this->createForm(caracterizacionType::class, $caracterizacion,
                array('compania' => $idCompania, 'method' => 'POST', 'Procesos' => $procesos,
                    'action' => $this->generateUrl('caracterizacion_guardar', array('idProceso' => $idProceso))));
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {

            $fechaActual = new \DateTime('now');
            $caracterizacion->setFechaCrea($fechaActual);
            $caracterizacion->setProcesosxCaracterizacion($procesos);
            $caracterizacion->setEstado(1);
            $caracterizacion->setNombreAprobado('prueba');
            $caracterizacion->setFechaAprobado($fechaActual);

            $usuariosxCaracterizacion = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $caracterizacion->setUsuariosxCaracterizacion($usuariosxCaracterizacion);

            $bd->persist($caracterizacion);
            $id_caracterizacion = $caracterizacion->getId();

            foreach ($caracterizacion->getSelResponsablexProceso() as $Responsables) {
                $id_responsable = $Responsables->getId();
                $this->addresponsablesxproceso($id_responsable, $id_caracterizacion);
            }

            foreach ($caracterizacion->getSelRequisitosxProceso() as $Requisitos) {
                $id_requisito = $Requisitos->getId();
                $this->addRequisitoxproceso($id_requisito, $id_caracterizacion);
            }

            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }
        return $this->render('Calidad\caracterizacion\nuevaCaracterizacion.html.twig',
                        array('form' => $FORMA->createView(), 'idProceso' => $idProceso));
    }

    public function addRequisitoxproceso($id_requisito, $id_caracterizacion) {

        $bd = $this->getDoctrine()->getManager();
        $requisitosxprocesos = new requisitosxproceso();

        $CaracterizacionxRequisitosxprocesos = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($id_caracterizacion);
        $requisitosxprocesos->setCaracterizacionxRequisitosxprocesos($CaracterizacionxRequisitosxprocesos);

        $RequisitosxRequisitosxprocesos = $bd->getRepository('App\Entity\Calidad\\requisitos')->find($id_requisito);
        $requisitosxprocesos->setRequisitosxRequisitosxprocesos($RequisitosxRequisitosxprocesos);

        $bd->persist($requisitosxprocesos);
        $bd->flush();

        return new \Symfony\Component\HttpFoundation\Response("Hecho");
    }

    public function addresponsablesxproceso($id_responsable, $id_caracterizacion) {

        $bd = $this->getDoctrine()->getManager();
        $responsablesxprocesos = new responsablesxprocesos();

        $CaracterizacionxResponsablesxprocesos = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($id_caracterizacion);
        $responsablesxprocesos->setCaracterizacionxResponsablesxprocesos($CaracterizacionxResponsablesxprocesos);

        $CargosxResponsablesxprocesos = $bd->getRepository('App\Entity\Nomina\cargos')->find($id_responsable);
        $responsablesxprocesos->setCargosxResponsablesxprocesos($CargosxResponsablesxprocesos);

        $bd->persist($responsablesxprocesos);
        $bd->flush();

        return new \Symfony\Component\HttpFoundation\Response("Hecho");
    }

    public function obsoletosCaracterizacion($idCaracterizacion, Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($idCaracterizacion);
        $idProceso = $caracterizacion->getIdProceso();

        $FORMA3 = $this->createFormBuilder($caracterizacion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('caracterizacion_obsoleto', array('idCaracterizacion' => $idCaracterizacion))))
                ->add('notaobsoleto', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de obsolecencia'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA3->handleRequest($request);

        if ($FORMA3->isValid()) {

            $caracterizacion->setEstado(0);
            $caracterizacion->setFechaobsoleto(new \DateTime('now'));
            $usuariosobsoletoxCaracterizacion = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $caracterizacion->setUsuariosobsoletoxCaracterizacion($usuariosobsoletoxCaracterizacion);
            $bd->flush();
            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }

        return $this->render('Calidad\caracterizacion\verCaracterizacion.html.twig', array('form3' => $FORMA->createView()));
    }

    public function BajaCaracterizacion($idCaracterizacion, Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($idCaracterizacion);
        $idProceso = $caracterizacion->getIdProceso();

        $FORMA5 = $this->createFormBuilder($caracterizacion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('caracterizacion_dardebaja', array('idCaracterizacion' => $idCaracterizacion))))
                ->add('nota_baja', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Baja'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Guardar'))
                ->getForm();

        $FORMA5->handleRequest($request);

        if ($FORMA5->isValid()) {

            $caracterizacion->setEstado(1);
            $caracterizacion->setFechaBaja(new \DateTime('now'));
            $usuariosbajaxCaracterizacion = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $caracterizacion->setUsuariosbajaxCaracterizacion($usuariosbajaxCaracterizacion);
            $bd->flush();
            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }

        return $this->render('Calidad\caracterizacion\verCaracterizacion.html.twig', array('form3' => $FORMA->createView()));
    }

    public function listarObsoletosCaracterizacion($idProceso, Request $request, PaginatorInterface $paginator) {
        $bd = $this->getDoctrine()->getManager();
        $procesos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
        $registroAc = $bd->getRepository('App\Entity\Calidad\actividadesxproceso')->findBy(array('idProceso' => $idProceso), array('id' => 'asc'));
        $archivos = $bd->getRepository('App\Entity\Calidad\documentosexternos')->findBy(array('idProceso' => $idProceso), array('id' => 'asc'));
        $registro = $bd->getRepository('App\Entity\Calidad\caracterizacion')->findBy(array('estado' => 0, 'idProceso' => $idProceso));

        if (!empty($registro)) {
            foreach ($registro as $dato) {
                $id_caracterizacion = $dato->getId();
                break;
            }
            $varCaracterizacion = "'id_caracterizacion'=>$id_caracterizacion";
        } else {
            $varCaracterizacion = "";
        }

        $pagination = $paginator->paginate($registro, $request->query->getInt('page', 1), 1);
        return $this->render('Calidad\caracterizacion\listarCaracterizacionObs.html.twig', array('registro' => $pagination,
                    'procesos' => $procesos,
                    'registroAc' => $registroAc,
                    'archivos' => $archivos,
                    $varCaracterizacion
        ));
    }

    public function editarCaracterizacion($idCaracterizacion) {

        $bd = $this->getDoctrine()->getManager();
        $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($idCaracterizacion);
        $idCompania = $this->getUser()->getIdCompania();
        $idProceso = $caracterizacion->getIdProceso();
        $procesos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
        $FORMA = $this->createForm(caracterizacionType::class, $caracterizacion, array(
            'compania' => $idCompania,
            'action' => $this->generateUrl('caracterizacion_actualizar', array('idCaracterizacion' => $idCaracterizacion)),
            'method' => 'POST',
            'Procesos' => $procesos
        ));

        return $this->render('Calidad\caracterizacion\nuevaCaracterizacion.html.twig', array('form' => $FORMA->createView(), 'idCaracterizacion' => $idCaracterizacion, 'idProceso' => $idProceso));
    }

    public function actualizarCaracterizacion(Request $request, $idCaracterizacion) {

        $bd = $this->getDoctrine()->getManager();
        $caracterizacion = $bd->getRepository('App\Entity\Calidad\caracterizacion')->find($idCaracterizacion);
        $idCompania = $this->getUser()->getIdCompania();
        $idProceso = $caracterizacion->getIdProceso();
        $procesos = $bd->getRepository('App\Entity\Calidad\procesos')->find($idProceso);
        $FORMA = $this->createForm(caracterizacionType::class, $caracterizacion, array(
            'compania' => $idCompania,
            'method' => 'POST',
            'Procesos' => $procesos,
            'action' => $this->generateUrl('caracterizacion_actualizar', array('idCaracterizacion' => $idCaracterizacion))));
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {
            $bd->persist($caracterizacion);

            $id_caracterizacion = $caracterizacion->getId();
            $this->removeresponsablesxproceso($id_caracterizacion);
            $this->removerequisitossxproceso($id_caracterizacion);
            foreach ($caracterizacion->getSelResponsablexProceso() as $Responsables) {
                $id_responsable = $Responsables->getId();
                $this->addresponsablesxproceso($id_responsable, $id_caracterizacion);
            }

            foreach ($caracterizacion->getSelRequisitosxProceso() as $Requisitos) {
                $id_requisito = $Requisitos->getId();
                $this->addRequisitoxproceso($id_requisito, $id_caracterizacion);
            }
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');
            return $this->redirectToRoute('caracterizacion_ver', array('idProceso' => $idProceso));
        }
        return $this->render('Calidad\caracterizacion\nuevaCaracterizacion.html.twig',
                        array('form' => $FORMA->createView(), 'idProceso' => $idProceso));
    }

    public function removeresponsablesxproceso($id_caracterizacion) {
        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\\responsablesxprocesos')->findBy(array('idCaracterizacion' => $id_caracterizacion));
        foreach ($registro as $remover) {
            $remover->getId();
            $bd->remove($remover);
        }
        $bd->flush();
        return new \Symfony\Component\HttpFoundation\Response("Hecho");
    }

    public function removerequisitossxproceso($id_caracterizacion) {
        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\\requisitosxproceso')->findBy(array('idCaracterizacion' => $id_caracterizacion));
        foreach ($registro as $remover) {
            $remover->getId();
            $bd->remove($remover);
        }
        $bd->flush();
        return new \Symfony\Component\HttpFoundation\Response("Hecho");
    }

    public function listarDocExternos(Request $request, PaginatorInterface $paginator) {

        $idCompania = $this->getUser()->getIdCompania();
        $bd = $this->getDoctrine()->getManager();

        $cadenaFiltros = $request->query->get('form');
        $filtroProceso = $cadenaFiltros['selProceso'];
        $filtroTiposnorma = $cadenaFiltros['tipoNorma'];

        if (empty($filtroProceso) && !empty($filtroTiposnorma)) {
            $registro = $bd->getRepository('App\Entity\Calidad\documentosexternos')->findBy(
                    array('idReqNorma' => $filtroTiposnorma));
        }

        if (!empty($filtroProceso) && empty($filtroTiposnorma)) {
            $registro = $bd->getRepository('App\Entity\Calidad\documentosexternos')->findBy(
                    array('idProceso' => $filtroProceso));
        }

        if (!empty($filtroProceso) && !empty($filtroTiposnorma)) {
            $registro = $bd->getRepository('App\Entity\Calidad\documentosexternos')->findBy(
                    array('idProceso' => $filtroProceso, 'idReqNorma' => $filtroTiposnorma));
        }

        if (empty($registro)) {
            $registro = $bd->getRepository('App\Entity\Calidad\documentosexternos')->findAll();
        }

        if (empty($filtroProceso)) {
            $filtroProceso = 0;
        }

        if (empty($filtroTiposnorma)) {
            $filtroTiposnorma = 0;
        }

        $FORMA = $this->createFormBuilder($registro, array(
                    'method' => 'GET', 'action' => $this->generateUrl('doc_externos_listar')))
                ->add('selProceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\procesos',
                    'choice_value' => 'id',
                    'choice_label' => 'proceso',
                    'label' => 'Procesos',
                    'placeholder' => 'Seleccione un Proceso',
                    'data' => $bd->getRepository("App\Entity\Calidad\procesos")->find($filtroProceso),
                    'query_builder' => function(\App\Repository\Calidad\procesosRepository $er) use ($idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->setParameter('a', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('tipoNorma', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\\tipo_req_norma',
                    'choice_value' => 'id',
                    'choice_label' => 'reqNorma',
                    'label' => 'Req Norma',
                    'placeholder' => 'Seleccione un Tipo de Norma',
                    'data' => $bd->getRepository("App\Entity\Calidad\\tipo_req_norma")->find($filtroTiposnorma),
                    'query_builder' => function(\App\Repository\Calidad\tipo_req_normaRepository $er) use ($idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->setParameter('a', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->getForm();

        $pagination = $paginator->paginate($registro, $request->query->getInt('page', 1), 50);

        return $this->render('Calidad\documentacion\listarDocExternos.html.twig',
                        array('registro' => $pagination, 'form' => $FORMA->createView()));
    }

}
