<?php

namespace App\Controller\Calidad;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Calidad\accionesmejoraType;
use App\Entity\Calidad\analisis_accion;
use App\Entity\Calidad\causas_analisis;
use App\Entity\Calidad\actividades_accionmejora;
use App\Form\Calidad\actividades_accionmejoraType;
use App\Entity\Calidad\seguimiento_actividad;
use App\Entity\Calidad\noconformes;
use App\Entity\Calidad\estados_noconformidad;
use App\Entity\Calidad\accionesmejora;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\Central\servNotificaciones;

class accionesMejoraController extends AbstractController {

    public function ListarAccionesMejora(Request $request, PaginatorInterface $paginator) {

        $bd = $this->getDoctrine()->getManager();
        $ban = false;
        $adFechaini = " ";
        $idCompania = $this->getUser()->getIdCompania();
        $procesosAccionMejora = $bd->getRepository('App\Entity\Calidad\procesos')->findBy(
                array('idCompania' => $idCompania));
        //dump($request->request->get('form'));
        //dump($request->query);exit;
        //$procesoCaracte = $request->query->get("vProceso"));

        if ($request->query->get("vProceso")) {
            $vectorProcesosxcompania[] = $request->query->get("vProceso");
        } else {
            foreach ($procesosAccionMejora as $noconf) {
                $vectorProcesosxcompania[] = $noconf->getId();
            }

            if (empty($vectorProcesosxcompania)) {
                $vectorProcesosxcompania[] = array(0);
            }
        }

        if ($request->request->get('form')) {
            $valores = $request->request->get('form');
            $fechaini = $valores["fecha_ini"];
            $fechafin = $valores["fecha_fin"];


            if (!empty($valores["subproceso"])) {
                $subproceso = $valores["subproceso"];
                $adSubProceso = " and  a.idSubproceso = " . $subproceso . "";
            } else {
                $adSubProceso = "";
                $subproceso = "";
            }
            if (!empty($valores["SelProceso"])) {
                $proceso = $valores["SelProceso"];

                $adProceso = " and  a.idProceso = " . $proceso . "";
            } else {
                $adProceso = "";
                $proceso = "";
            }
            if (!empty($valores["microproceso"])) {
                $microproceso = $valores["microproceso"];
                $adMicroproceso = " and  a.idMicroproceso = " . $microproceso . "";
            } else {
                $microproceso = "";
                $adMicroproceso = "";
            }
            if (!empty($valores["tipo"])) {
                $tipo = $valores["tipo"];
                $adTipo = " and  a.idTipoaccion = " . $tipo . "";
            } else {
                $tipo = "";
                $adTipo = "";
            }
            if (!empty($valores["origen"])) {
                $origen = $valores["origen"];
                $adOrigen = " and  a.idOrigenaccion = " . $origen . "";
            } else {
                $origen = "";
                $adOrigen = "";
            }
            if (!empty($valores["responsable"])) {
                $responsable = $valores["responsable"];
                $adResponsable = " and  a.idResponsable = " . $responsable . "";
            } else {
                $responsable = "";
                $adResponsable = "";
            }
            if (!empty($valores["estado"])) {
                $estado = $valores["estado"];
                $adEstado = " and  a.idEstado= " . $estado . "";
            } else {
                $estado = "";
                $adEstado = "";
            }

            $dql = "select a from App\Entity\Calidad\accionesmejora a WHERE a.fechacrea"
                    . " between '$fechaini' and '$fechafin 23:59:59' " . $adProceso . $adSubProceso . $adMicroproceso . $adTipo . $adOrigen . $adResponsable . $adEstado . " and a.idProceso in (:VectorProcesos) order by a.id ";
            $query = $bd->createQuery($dql);

            //dump($query);exit;
        } else {
            $ban = true;
            $fechaini = new \DateTime();
            $fechaini = $fechaini->format('Y-m-01');
            $fechafin = new \DateTime();
            $fechafin = $fechafin->format('Y-m-d');
            $subproceso = "";
            $proceso = "";

            $dql = "select a from App\Entity\Calidad\accionesmejora a "
                    . " where a.fechacrea between '$fechaini' and '$fechafin  23:59:59'  "
                    . " and a.idEstado=1 and a.idProceso IN(:VectorProcesos) order by a.id ";

            $query = $bd->createQuery($dql);
        }
        $query->setParameter(':VectorProcesos', $vectorProcesosxcompania);
        $registro = $query->getResult();        

        $pagination = $paginator->paginate($registro, $request->query->getInt('page', 1), 20);

        $FORMA = $this->createFormBuilder($registro, array(
                    'method' => 'POST', 'action' => $this->generateUrl('acciones_mejora_listar')))
                ->add('fecha_ini', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Desde', 'widget' => 'single_text'))
                ->add('fecha_fin', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('label' => 'Hasta', 'widget' => 'single_text'))
                ->add('SelProceso', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\procesos',
                    'choice_value' => 'id',
                    'choice_label' => 'proceso',
                    'label' => 'Procesos',
                    'query_builder' => function(\App\Repository\Calidad\procesosRepository $er ) use ( $idCompania ) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->setParameter('a', $idCompania)
                                ->orderBy('w.id', 'ASC');
                    }
                ))
                ->add('subproceso', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array('placeholder' => 'Escoja un proceso'))
                ->add('microproceso', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array('placeholder' => 'Escoja un microproceso'))
                ->add('tipo', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\\tipo_accionmejora',
                    'choice_value' => 'id',
                    'choice_label' => 'tipoAccionmejora',
                    'label' => 'Tipo'
                ))
                ->add('origen', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\origen_accionmejora',
                    'choice_value' => 'id',
                    'choice_label' => 'origenAccionmejora',
                    'label' => 'Origen'
                ))
                ->add('responsable', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Central\usuarios',
                    'choice_value' => 'id',
                    'choice_label' => 'nombre',
                    'label' => 'Responsable'
                ))
                ->add('estado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\estados_accionmejora',
                    'choice_value' => 'id',
                    'choice_label' => 'estadoAccionmejora',
                    'label' => 'Estado'
                ))
                ->add('filtrar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'filtrar'))
                ->getForm();

        //$FORMA->handleRequest($request);

        if (empty($subproceso)) {
            $subproceso = "";
        }
        if (empty($proceso)) {
            $proceso = "";
        }
        if (empty($microproceso)) {
            $microproceso = "";
        }
        if (empty($tipo)) {
            $tipo = "";
        }
        if (empty($origen)) {
            $origen = "";
        }
        if (empty($responsable)) {
            $responsable = "";
        }
        if ($ban) {
            $estado = 1;
        } elseif (empty($estado)) {
            $estado = "";
        }
        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);
        return $this->render('Calidad\accionesmejora\listarAccionesMejora.html.twig', array('form' => $FORMA->createView(), 'registro' => $pagination, 'microproceso' => $microproceso, 'subproceso' => $subproceso, 'proceso' => $proceso, 'fecha_ini' => $fechaini, 'fecha_fin' => $fechafin, 'tipo' => $tipo, 'origen' => $origen, 'responsable' => $responsable, 'estado' => $estado, 'permisos' => $permisos));
    }

    public function nuevoAccionesMejora($ban, $idReg, $idConceptoAuditoria, $idTmando, $idPl, $idMate, Request $request) {

        $bd = $this->getDoctrine()->getManager();
        $accionesmejora = new accionesmejora();
        $ejecucionAuditoria = "";
        $tmando_analisisgerencial = "";
        $tmandoProceso = 0;
        $proceso = 0;

        if (!empty($request->query->get('riesgoSeguimiento'))) {
            $riesgoSeguimiento = $bd->getRepository(\App\Entity\Calidad\riesgoSeguimiento::class)->find($request->query->get('riesgoSeguimiento'));
//            dump($riesgoSeguimiento);
//            exit;
//             if ($riesgoSeguimiento) {
//                foreach ($riesgoSeguimiento as $seguimiento) {
            $txt1 = $riesgoSeguimiento->getDescripcion();
//                }
//            }
            $proceso = $riesgoSeguimiento->getIdProceso();
            $ban = 11;
            $origen = 4;
            $tipoAccion = 3;
        }

        //Pacho: se obtienen los datos de la ejecucion de auditoria con el idConceptoAuditoria
        if ($idConceptoAuditoria != 0) {
            $ejecucionAuditoria = $bd->getRepository('App\Entity\Calidad\ejecucionauditoria')->findBy(
                    array('idConceptoauditoria' => $idConceptoAuditoria));
            if ($ejecucionAuditoria) {
                foreach ($ejecucionAuditoria as $ejecucion) {
                    $txt1 = $ejecucion->getObservacion();
                }
            }
            // se establece ban en 5 para que el boton volver del twig registronoconforme regrese a la ejecución.
            $ban = 5;
        }

        if ($idPl != 0) {
            $protocolo = $bd->getRepository('App\Entity\Calidad\pseg_protocolo_londres')->findBy(array('id' => $idPl));
            if ($protocolo) {
                foreach ($protocolo as $proto) {
                    $txt1 = $proto->getNostasAprobacion();
                    $proce_clas = $proto->getProcesoClasifica();
                }
            }

            // se establece ban en 6 para que el boton volver del twig verSuceso regrese a la ejecución.
            $ban = 6;
        }

        //fin proveniente ejecucion auditoria
        // ban = 3 viene desde registro pqrsf
        $registroB = "";
        if ($ban == 3) {
            $registroB = $bd->getRepository('App\Entity\Calidad\buzones_registro_detalle')->find($idReg);
            $txt1 = $registroB->getDescripcion();
        }
        if (empty($txt1)) {
            $txt1 = "";
        }


        if ($idTmando != 0) {

            $tmando_analisisgerencial = $bd->getRepository('App\Entity\Calidad\\tmando_analisisgerencial')->find($idTmando);
            $ObjetivoGerencial = $tmando_analisisgerencial->getAnalisis();
            $tmandoProceso = $tmando_analisisgerencial->getIdProceso();
            $ban = 9;
        }
        if (empty($ObjetivoGerencial)) {
            $ObjetivoGerencial = "";
        }
        if (empty($protocolo)) {
            $protocolo = "";
        }

        if ($idMate != 0) {
            $materializacion = $bd->getRepository('App\Entity\Calidad\\riesgo_materializacion')->findBy(array('id' => $idMate));
            if ($materializacion) {
                foreach ($materializacion as $mate) {
                    $txt1 = $mate->getDescripcion();
                    $proceso = $mate->getIdProceso();
                }
            }

            $origen = 4;
            // se establece ban en 10 para que el boton volver del twig verSuceso regrese a la materializaci�n.
            $ban = 10;
        }
        $usu = $bd->getRepository('App\Entity\Central\usuarios')->findBy(['idcompania' => $this->getUser()->getIdCompania()]);
        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);
        foreach ($permisos as $value) {
            if ($value->getIdFuncionalidad() == 871 and $value->getEscritura() == true and $value->getSeguimiento() == null) {
                $usu = [$this->getUser()->getId()];
            }
        }

        if (empty($origen)) {
            $origen = 0;
        }

        if (empty($tipoAccion)) {
            $tipoAccion = 0;
        }
        if (empty($riesgoSeguimiento)) {
            $riesgoSeguimiento = 0;
        }

        $FORMA = $this->createForm(accionesmejoraType::class, $accionesmejora, array('ejecucionAu' => $ejecucionAuditoria, 'protocolo' => $protocolo, 'proceso' => $proceso,
            'entity_manager' => $bd, 'compania' => $this->getUser()->getIdCompania(), 'origen' => $origen,
            'txt' => $txt1, 'ObjetivoGerencial' => $ObjetivoGerencial, 'usu' => $usu, 'tipoAccion' => $tipoAccion,
            'method' => 'POST', 'action' => $this->generateUrl('acciones_mejora_guardar', array('btnvolver' => $ban, 'idConceptoAuditoria' => $idConceptoAuditoria,
                'ban' => $ban, 'idReg' => $idReg, 'idTmando' => $idTmando, 'idPl' => $idPl,
                'idMate' => $idMate, 'riesgoSeguimiento' => $request->query->get('riesgoSeguimiento')))));


        return $this->render('Calidad\accionesmejora\nuevaAccionMejora.html.twig', array('form' => $FORMA->createView(),
                    'ban' => 1,
                    'protocolo' => $protocolo,
                    'riesgoSeguimiento' => $riesgoSeguimiento,
                    'registroB' => $registroB, 'btnvolver' => $ban, 'idMate' => $idMate,
                    'ejecucionAu' => $ejecucionAuditoria, 'idPl' => $idPl, 'tmandoProceso' => $tmandoProceso,
                    'analisisGerencial' => $tmando_analisisgerencial));
    }

    public function guardarAccionesMejora(Request $request, $idConceptoAuditoria, $ban, $idReg, $idTmando, $idPl, $idMate, servNotificaciones $notifica) {


        $bd = $this->getDoctrine()->getManager();
        $accionesmejora = new accionesmejora();
        $registroB = "";
        $tmandoProceso = 0;
        $btnvolver = 0;
        $usu = $bd->getRepository('App\Entity\Central\usuarios')->findBy(['idcompania' => $this->getUser()->getIdCompania()]);
        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);
        foreach ($permisos as $value) {
            if ($value->getIdFuncionalidad() == 871 and $value->getEscritura() == true and $value->getSeguimiento() == null) {
                $usu = [$this->getUser()->getId()];
            }
        }

        if ($idTmando != 0) {
            $tmando_analisisgerencialE = $bd->getRepository('App\Entity\Calidad\\tmando_analisisgerencial')->find($idTmando);
            $tmandoProceso = $tmando_analisisgerencialE->getIdProceso();
            $btnvolver = 9;
        } else {
            $tmando_analisisgerencialE = null;
        }

        if ($ban == 3) {
            $registroB = $bd->getRepository('App\Entity\Calidad\buzones_registro_detalle')->find($idReg);
        }

        if ($ban == 11) {
            $btnvolver = 11;
            $registroB = $bd->getRepository(\App\Entity\Calidad\riesgoSeguimiento::class)->find($request->query->get('riesgoSeguimiento'));
        }
        $seguimi = $request->query->get('riesgoSeguimiento');

        $FORMA = $this->createForm(accionesmejoraType::class, $accionesmejora, array('entity_manager' => $bd, 'compania' => $this->getUser()->getIdCompania(), 'usu' => $usu,
            'method' => 'POST',
            'action' => $this->generateUrl('acciones_mejora_guardar', array('btnvolver' => $ban, 'idConceptoAuditoria' => $idConceptoAuditoria,
                'ban' => $ban, 'idReg' => $idReg, 'idTmando' => $idTmando, 'idPl' => $idPl,
                'idMate' => $idMate, 'riesgoSeguimiento' => $request->query->get('riesgoSeguimiento')))));

        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {

            $pref = $accionesmejora->getProcesoxAccionesmejora()->getPrefijo();
            $usuariosxAccionesmejora = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $accionesmejora->setUsuariosxAccionesmejoraCrea($usuariosxAccionesmejora);
            $accionesmejora->setFechacrea(new \DateTime('now'));
            $estado = $bd->getRepository('App\Entity\Calidad\estados_accionmejora')->find(1);
            $accionesmejora->setEstadosAccionMejoraxAccionesmejora($estado);
            $bd->persist($accionesmejora);
            $bd->flush();
            $id = $accionesmejora->getId();

            $codigo = "AM" . "-" . "$pref" . "-" . "00" . "$id";
            $accionesmejora->setCodigo($codigo);
            $bd->flush();

            //actualizar accion mejora en detalle de buzon
            if ($ban == 3) {

                $accionMejoraxDetalle = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($id);
                $registroB->setAccionmejoraxDetalle($accionMejoraxDetalle);
                $bd->flush($registroB);
            }

            if ($ban == 11) {

                $accionMejoraxDetalle = $bd->getRepository(accionesmejora::class)->find($id);
                $registroB->setAccionesmejoraxRiesgoseguimiento($accionMejoraxDetalle);
                $bd->flush($registroB);

                return $this->redirectToRoute('fichaSeguimientoRiesgos', array(
                            'id' => $request->query->get('riesgoSeguimiento')));
            }

            if ($idPl != 0) {

                $protocolo = $bd->getRepository('App\Entity\Calidad\pseg_protocolo_londres')->find($idPl);
                $protocolo->setIdAccionmejora($id);
                $bd->flush($protocolo);

                return $this->redirectToRoute('sucesos_ver', array('id' => $idPl));
            }

            if ($idTmando != 0) {

                $accionMejoraxDetalle = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($id);
                $tmando_analisisgerencial = $bd->getRepository('App\Entity\Calidad\\tmando_analisisgerencial')->find($idTmando);

                //$tmandoProceso = $tmando_analisisgerencial->getIdProceso();
                $tmando_analisisgerencial->setAccionesmejoraxTmandoAnalisisgerencial($accionMejoraxDetalle);
                $bd->flush($tmando_analisisgerencial);
            }
            if ($idMate != 0) {

                $materializacion = $bd->getRepository('App\Entity\Calidad\\riesgo_materializacion')->find($idMate);
                $materializacion->setIdAccionmejora($id);
                $bd->flush($materializacion);

                return $this->redirectToRoute('materializacion_riesgos_ver', array('id' => $idMate));
            }
            //-------------------------------------------
            if ($idConceptoAuditoria != 0) {
                $ejecucion = $bd->getRepository('App\Entity\Calidad\ejecucionauditoria')->findBy(array('idConceptoauditoria' => $idConceptoAuditoria));
                if ($ejecucion) {
                    foreach ($ejecucion as $eje) {
                        $eje->setAccionmejoraxEjecucionauditoria($bd->getRepository('App\Entity\Calidad\accionesmejora')->find($id));

                        $idAuditoria = $eje->getConceptosauditoriaxEjecucionauditoria()->getProcesosxauditoriasxConceptosauditoria()->getIdAuditoria();
                        $idProceso = $eje->getConceptosauditoriaxEjecucionauditoria()->getProcesosxauditoriasxConceptosauditoria()->getIdProceso();
                        $idSubproceso = $eje->getConceptosauditoriaxEjecucionauditoria()->getProcesosxauditoriasxConceptosauditoria()->getIdSubproceso();
                        if (!$idSubproceso) {
                            $idSubproceso = 0;
                        }
                        $bd->flush($eje);
                        $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

                        return $this->redirectToRoute('auditoria_ejecutar', array('idAuditoria' => $idAuditoria, 'idProceso' => $idProceso,
                                    'idSubproceso' => $idSubproceso));
                    }
                }
            }
            $microp = "";
            if ($accionesmejora->getMicroprocesoxAccionesmejora() != null) {
                $microp = $accionesmejora->getMicroprocesoxAccionesmejora()->getMicroproceso();
            }
            //  dump($accionesmejora->getMicroprocesoxAccionesmejora()); exit;
            //->getProcesoxAccionesmejora->getProceso()
            $idG = $FORMA->getData()->getUsuariosxAccionesmejora();
            $usuarioC = $bd->getRepository('App\Entity\Central\usuarios')->findOneBy(array('id' => $idG));
            $correoUsu[] = $usuarioC->getId();
            if (count($correoUsu) > 0) {
                $not1 = $notifica->crearNotificacion($correoUsu, 'Nueva acción de mejora', 
                    'Se ha creado la Accion de mejora:<br>
                   <div class="container" >
                        <div class="row">
                            <div class=col-md-2>Proceso:</div> 
                            <div class="col-md-3">'.$accionesmejora->getProcesoxAccionesmejora()->getProceso().'</div>
                        </div>
                        <div class="row">
                            <div class=col-md-2>Subroceso:</div> 
                            <div class="col-md-3">'.$accionesmejora->getSubprocesoxAccionesmejora()->getSubproceso().'</div>
                        </div>
                        <div class="row">
                            <div class=col-md-2>Microproceso:</div> 
                            <div class="col-md-3">'.$microp.'</div>
                        </div>
                         <div class="row">
                            <div class=col-md-2>Tipo de Accion:</div> 
                            <div class="col-md-3">'.$accionesmejora->getTipoAccionMejoraxAccionesmejora()->getTipoAccionmejora().'</div>
                        </div>
                         <div class="row">
                            <div class=col-md-2>Orígen:</div> 
                            <div class="col-md-3">'.$accionesmejora->getOrigenAccionMejoraxAccionesmejora()->getOrigenAccionmejora().'</div>
                        </div>
                        <div class="row">
                            <div class=col-md-2>Descripción:</div> 
                            <div class="col-md-3">'.$accionesmejora->getDescripccion().'</div>
                        </div>
                         <div class="row">
                            <div class=col-md-2>Objetivo:</div> 
                            <div class="col-md-3">'.$accionesmejora->getObjetivo().'</div>
                        </div>
                    </div>', 
                   'acciones_mejora_ver', 
                    1, 
                    'idAccion:' . $accionesmejora->getId() . '');
            }


            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('acciones_mejora_ver', array('idAccion' => $id, 'idReg' => $idReg));
        }
        $seguimi = $request->query->get('riesgoSeguimiento');
        if (empty($seguimi)) {
            $seguimi = 0;
        }

        return $this->render('Calidad\accionesmejora\nuevaAccionMejora.html.twig', array('form' => $FORMA->createView(), 'ban' => 1, 'btnvolver' => $btnvolver,
                    'registroB' => $registroB, 'tmandoProceso' => $tmandoProceso,
                    'idPl' => $idPl, 'idMate' => $idMate, 'analisisGerencial' => $tmando_analisisgerencialE,
                    'riesgoSeguimiento' => $bd->getRepository(\App\Entity\Calidad\riesgoSeguimiento::class)->find($seguimi)));
    }

    public function verAccionesMejora($idAccion, $idReg, $idConceptoAuditoria, $idPl, $idMate, Request $request) {


        $bd = $this->getDoctrine()->getManager();
        $accionesmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $id = $idAccion;
        $registroB = "";
        if ($idReg > 0) {
            $registroB = $bd->getRepository('App\Entity\Calidad\buzones_registro_detalle')->find($idReg);
        }
        $ban = 0;
        $ejecucionAuditoria = "";
        //Pacho: se obtienen los datos de la ejecucion de auditoria con el idConceptoAuditoria
        if ($idConceptoAuditoria != 0) {
            $ejecucionAuditoria = $bd->getRepository('App\Entity\Calidad\ejecucionauditoria')->findBy(array('id' => $idConceptoAuditoria));
            if ($ejecucionAuditoria) {
                foreach ($ejecucionAuditoria as $ejecucion) {
                    $txt1 = $ejecucion->getObservacion();
                }
            }
            // se establece ban en 5 para que el boton volver del twig registronoconforme regrese a la ejecución.
            $ban = 5;
        }
        //fin proveniente ejecucion auditoria
        if ($request->query->get('volver') == 3) {
            $ban = 3;
            $seguimientoId = $request->query->get('seguimiento');
        }

        if (empty($seguimientoId)) {
            $seguimientoId = 0;
        }

        $FORMA3 = $this->createFormBuilder($accionesmejora, array(
                    'method' => 'POST', 'action' => $this->generateUrl('acciones_mejora_visto', array('idAccion' => $id))))
                ->add('fechacierra', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('widget' => 'single_text'))
                ->add('notacierre', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();

        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(
                ['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);

        return $this->render('Calidad\accionesmejora\verAccionMejora.html.twig', array('form' => $FORMA3->createView(),
                    'registro' => $accionesmejora,
                    'registroB' => $registroB,
                    'ejecucionAu' => $ejecucionAuditoria,
                    'btnvolver' => $ban,
                    'idPl' => $idPl,
                    'idMate' => $idMate, 'seguimientoId' => $seguimientoId,
                    'permisos' => $permisos
        ));
    }

    public function vistoAccionesMejora(Request $request, $idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $accionesmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $FORMA3 = $this->createFormBuilder($accionesmejora, array(
                    'method' => 'POST', 'action' => $this->generateUrl('acciones_mejora_visto', array('idAccion' => $idAccion))))
                ->add('fechacierra', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('widget' => 'single_text'))
                ->add('notacierre', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();
        $FORMA3->handleRequest($request);
        if ($FORMA3->isValid()) {

            $accionesmejora->setFechacierra(new \DateTime('now'));
            $accionesmejora->setUsuariosxAccionesmejoraCierra($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
            //$accionesmejora->setIdEstado(6);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('acciones_mejora_ver', array('idAccion' => $idAccion));
        }

        return $this->redirectToRoute('acciones_mejora_ver', array('idAccion' => $idAccion));
    }

    public function editarAccionesMejora($idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $idMate = 0;
        $accionesmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $descripcion = $accionesmejora->getDescripccion();
        $objetivo = $accionesmejora->getObjetivo();
        $ban = 2;
        $proceso = $accionesmejora->getIdProceso();
        $subproceso = $accionesmejora->getIdSubproceso();
        $microproceso = $accionesmejora->getIdMicroproceso();
        $origen = $accionesmejora->getIdOrigenaccion();
        $usu = $bd->getRepository('App\Entity\Central\usuarios')->findBy(['idcompania' => $this->getUser()->getIdCompania()]);
        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);
        foreach ($permisos as $value) {
            if ($value->getIdFuncionalidad() == 871 and $value->getEscritura() == true and $value->getSeguimiento() == null) {
                $usu = [$this->getUser()->getId()];
            }
        }
        $FORMA = $this->createForm(accionesmejoraType::class, $accionesmejora, array('origen' => $origen, 'microproceso' => $microproceso,
            'subproceso' => $subproceso, 'proceso' => $proceso,
            'objetivo' => $objetivo, 'descripcion' => $descripcion,
            'entity_manager' => $bd, 'compania' => $this->getUser()->getIdCompania(),
            'usu' => $usu, 'method' => 'POST',
            'action' => $this->generateUrl('acciones_mejora_actualizar', array('idAccion' => $idAccion))));

        return $this->render('Calidad\accionesmejora\nuevaAccionMejora.html.twig', array('form' => $FORMA->createView(), 'ban' => $ban, 'idMate' => $idMate,
                    'subproceso' => $subproceso, 'microproceso' => $microproceso, 'registroB' => ""));
    }

    public function actualizarAccionesMejora(Request $request, $idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $idMate = 0;
        $accionesmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $subproceso = $accionesmejora->getIdSubproceso();
        $microproceso = $accionesmejora->getIdMicroproceso();
        $usu = $bd->getRepository('App\Entity\Central\usuarios')->findBy(['idcompania' => $this->getUser()->getIdCompania()]);
        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);
        foreach ($permisos as $value) {
            if ($value->getIdFuncionalidad() == 871 and $value->getEscritura() == true and $value->getSeguimiento() == null) {
                $usu = [$this->getUser()->getId()];
            }
        }
        if ($accionesmejora->getIdMicroproceso() == null) {
            $ban = 2;
        } else {
            $ban = 0;
        }
        $FORMA = $this->createForm(accionesmejoraType::class, $accionesmejora, array('entity_manager' => $bd, 'compania' => $this->getUser()->getIdCompania(),
            'usu' => $usu, 'method' => 'POST', 'action' => $this->generateUrl('acciones_mejora_actualizar', array('idAccion' => $idAccion))));

        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {

            $accionesmejora->setFechamod(new \DateTime('now'));
            $usuariosxAccionesmejora = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $accionesmejora->setIdUsurimod($usuariosxAccionesmejora->getId());
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('acciones_mejora_ver', array('idAccion' => $idAccion));
        }

        return $this->render('Calidad\accionesmejora\nuevaAccionMejora.html.twig', array('form' => $FORMA->createView(), 'ban' => $ban, 'idMate' => $idMate,
                    'subproceso' => $subproceso, 'microproceso' => $microproceso));
    }

    public function nuevoAnalisisAccion($idAccion, $estado, $idAnalisis) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $regAcc = $bd->getRepository('App\Entity\Calidad\analisis_accion')->findBy(array('idAccionmejora' => $idAccion));


        if ($regAcc) {
            foreach ($regAcc as $idAc) {
                $idA = $idAc->getId();
            }

            $regAcc2 = $bd->getRepository('App\Entity\Calidad\analisis_accion')->find($idA);
            $analisisAccion = $regAcc2;
        } else {

            $analisisAccion = new analisis_accion();
        }
        $causas_analisis = new causas_analisis();

        $FORMA = $this->createFormBuilder($analisisAccion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('analisis_accion_guardar', array('idAccion' => $idAccion, 'idAnalisis' => $idAnalisis))))
                ->add('efecto', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Efecto'))
                ->getForm();

        $FORMA2 = $this->createFormBuilder($causas_analisis, array(
                    'method' => 'POST', 'action' => $this->generateUrl('causas_analisis_guardar', array('idAccion' => $idAccion, 'idAnalisis' => $idAnalisis))))
                ->add('tiposCausaxCausasAnalisis', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\\tipos_causa',
                    'choice_value' => 'id',
                    'choice_label' => 'tipoCausa',
                    'label' => 'Tipo Causa'
                ))
                ->add('causa', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Causas'))
                ->getForm();


        return $this->render('Calidad\accionesmejora\nuevoAnalisisCausas.html.twig', array('form' => $FORMA->createView(), 'form2' => $FORMA2->createView(),
                    'registro' => $registro, 'estado' => $estado));
    }

    public function guardarAnalisisAccion(Request $request, $idAccion, $idAnalisis) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);

        $regAcc = $bd->getRepository('App\Entity\Calidad\analisis_accion')->findBy(array('idAccionmejora' => $idAccion));
        if ($regAcc) {
            foreach ($regAcc as $idAc) {
                $idA = $idAc->getId();
            }

            $regAcc2 = $bd->getRepository('App\Entity\Calidad\analisis_accion')->find($idA);
            $analisisAccion = $regAcc2;
        } else {

            $analisisAccion = new analisis_accion();
        }

        $FORMA = $this->createFormBuilder($analisisAccion, array(
                    'method' => 'POST', 'action' => $this->generateUrl('analisis_accion_guardar', array('idAccion' => $idAccion, 'idAnalisis' => $idAnalisis))))
                ->add('efecto', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Efecto'))
                ->getForm();
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {

            if ($regAcc) {

                $usuariosxAnalisisAccionCrea = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
                $analisisAccion->setUsuariosxAnalisisAccionMod($usuariosxAnalisisAccionCrea);
                $analisisAccion->setFechamod(new \DateTime('now'));
                $bd->flush();
                $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

                return $this->redirectToRoute('analisis_accion_nuevo', array('idAccion' => $idAccion, 'estado' => 1, 'idAnalisis' => $idA));
            } else {

                $analisisAccion->setAccionesmejoraxAnalisisAccion($registro);
                $usuariosxAnalisisAccionCrea = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
                $analisisAccion->setUsuariosxAnalisisAccionCrea($usuariosxAnalisisAccionCrea);
                $analisisAccion->setFechacre(new \DateTime('now'));
                $bd->persist($analisisAccion);
                $registro->setIdEstado(2);
                $bd->flush();
                $id = $analisisAccion->getId();

                $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

                return $this->redirectToRoute('analisis_accion_nuevo', array('idAccion' => $idAccion, 'estado' => 1, 'idAnalisis' => $id));
            }
        }

        return $this->render('Calidad\accionesmejora\nuevoAnalisisCausas.html.twig', array('form' => $FORMA->createView(), 'estado' => 1, 'registro' => $registro));
    }

    public function guardarCausasAnalisis(Request $request, $idAccion, $idAnalisis) {

        $bd = $this->getDoctrine()->getManager();
        $causas_analisis = new causas_analisis();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $FORMA2 = $this->createFormBuilder($causas_analisis, array(
                    'method' => 'POST', 'action' => $this->generateUrl('causas_analisis_guardar', array('idAccion' => $idAccion, 'idAnalisis' => $idAnalisis))))
                ->add('tiposCausaxCausasAnalisis', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array('placeholder' => 'Seleccionar',
                    'class' => 'App\Entity\Calidad\\tipos_causa',
                    'choice_value' => 'id',
                    'choice_label' => 'tipoCausa',
                    'label' => 'Tipo Causa'
                ))
                ->add('causa', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Causas'))
                ->getForm();

        $FORMA2->handleRequest($request);

        if ($FORMA2) {

            $analisisAccionxCausasAnalisis = $bd->getRepository('App\Entity\Calidad\analisis_accion')->find($idAnalisis);
            $causas_analisis->setAnalisisAccionxCausasAnalisis($analisisAccionxCausasAnalisis);
            $bd->persist($causas_analisis);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('analisis_accion_nuevo', array('idAccion' => $idAccion, 'estado' => 1, 'idAnalisis' => $idAnalisis));
        }

        return $this->render('Calidad\accionesmejora\nuevoAnalisisCausas.html.twig', array('estado' => 1, 'form2' => $FORMA2->createView(), 'registro' => $registro));
    }

    public function eliminarCausasAnalisis($idAccion, $id, $idAnalisis) {
        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository('App\Entity\Calidad\causas_analisis')->find($id);
        try {
            $bd->remove($record);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }

        return $this->redirectToRoute('analisis_accion_nuevo', array('idAccion' => $idAccion, 'estado' => 1, 'idAnalisis' => $idAnalisis));
    }

    public function verEspinaPescado($idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);

        return $this->render('Calidad\accionesmejora\verEspinaPescado.html.twig', array('registro' => $registro));
    }

    public function planAccionMejora(Request $request, $idAccion, PaginatorInterface $paginator) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $registro1 = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->findBy(array('idAccionmejora' => $idAccion), array('id' => 'ASC'));
        //dump($registro1);exit;
        
        $permisos = $bd->getRepository('App\Entity\Calidad\perfiles')->findBy(['idPerfil' => $this->getUser()->getIdRol(), 'idCompania' => $this->getUser()->getIdCompania()]);
        $pagination = $paginator->paginate($registro1, $request->query->getInt('page', 1), 15);

        $accionmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $FORMA = $this->createFormBuilder($accionmejora, array(
                    'method' => 'POST', 'action' => $this->generateUrl('aprobacion_plan', array('idAccion' => $idAccion))))
                ->add('aprobacion', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array('Aprueba Plan de Acción' => 1, 'Rechaza Plan de Acción' => 0)))
                ->add('notavobo', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Aprobación/Rechazo'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();

        return $this->render('Calidad\accionesmejora\planAccionMejora.html.twig', array('form' => $FORMA->createView(), 'registro1' => $pagination,
                    'registro' => $registro, 'permisos' => $permisos));
    }

    public function nuevaActividad($idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $actividades_accionmejora = new actividades_accionmejora();
        $FORMA = $this->createForm(actividades_accionmejoraType::class, $actividades_accionmejora, array('method' => 'POST', 'action' => $this->generateUrl('actividad_guardar', array('idAccion' => $idAccion))));

        return $this->render('Calidad\accionesmejora\nuevaActividadPlan.html.twig', array('form' => $FORMA->createView(), 'registro' => $registro));
    }

    public function guardarActividad(Request $request, $idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $actividades_accionmejora = new actividades_accionmejora();
        $FORMA = $this->createForm(actividades_accionmejoraType::class, $actividades_accionmejora, array('method' => 'POST', 'action' => $this->generateUrl('actividad_guardar', array('idAccion' => $idAccion))));
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {
            $actividades_accionmejora->setAccionesmejoraxActividadesAccionmejora($registro);
            $usuariosxActividadesAccionmejoraCrea = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $actividades_accionmejora->setUsuariosxActividadesAccionmejoraCrea($usuariosxActividadesAccionmejoraCrea);
            $actividades_accionmejora->setFechacre(new \DateTime('now'));
            $bd->persist($actividades_accionmejora);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
        }

        return $this->render('Calidad\accionesmejora\nuevaActividadPlan.html.twig', array('form' => $FORMA->createView(), 'registro' => $registro));
    }

    public function verActividad(Request $request, $idAccion, $idActividad, $ban, PaginatorInterface $paginator) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $registro1 = $bd->getRepository('App\Entity\Calidad\seguimiento_actividad')->findBy(
                array('idActividad' => $idActividad), array('id' => 'ASC'));
        

        $actividad = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->find($idActividad);
        $FORMA = $this->createFormBuilder($actividad, array(
                    'method' => 'POST', 'action' => $this->generateUrl('actividad_visto_bueno', array('id' => $idActividad, 'idAccion' => $idAccion))))
                ->add('acepta', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array('Aprueba Plan de Actividad' => true, 'Rechaza Plan de Actividad' => false)))
                ->add('notacierre', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Aprobación/Rechazo'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();

        $seguimiento_actividad = new seguimiento_actividad();

        $FORMA2 = $this->createFormBuilder($seguimiento_actividad, array(
                    'method' => 'POST', 'action' => $this->generateUrl('seguimiento_actividad_guardar', array('idAccion' => $idAccion, 'idActividad' => $idActividad, 'ban' => $ban))))
                ->add('fechacre', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('widget' => 'single_text', 'label' => 'Fecha'))
                ->add('nota', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Descripción'))
                ->add('anexo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, array('data_class' => null, 'required' => false))
                ->add('solicitaCierre', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array('Si' => true, 'No' => false)))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();
        $pagination = $paginator->paginate($registro1, $request->query->getInt('page', 1), 15);

        return $this->render('Calidad\accionesmejora\verActividad.html.twig', array('form' => $FORMA->createView(), 'form2' => $FORMA2->createView(),
                    'registro' => $registro, 'registro1' => $pagination, 'idActividad' => $idActividad,
                    'regAct' => $actividad, 'ban' => $ban));
    }

    public function editarActividad($idAccion, $id) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $actividades_accionmejora = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->find($id);
        $FORMA = $this->createForm(actividades_accionmejoraType::class, $actividades_accionmejora, array('method' => 'POST', 'action' => $this->generateUrl('actividad_actualizar', array('idAccion' => $idAccion, 'id' => $id))));

        return $this->render('Calidad\accionesmejora\nuevaActividadPlan.html.twig', array('form' => $FORMA->createView(), 'registro' => $registro));
    }

    public function actualizarActividad(Request $request, $idAccion, $id) {

        $bd = $this->getDoctrine()->getManager();
        $registro = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $actividades_accionmejora = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->find($id);
        $FORMA = $this->createForm(actividades_accionmejoraType::class, $actividades_accionmejora, array('method' => 'POST', 'action' => $this->generateUrl('actividad_actualizar', array('idAccion' => $idAccion, 'id' => $id))));
        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {
            $usuariosxActividadesAccionmejoraMod = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $actividades_accionmejora->setUsuariosxActividadesAccionmejoraMod($usuariosxActividadesAccionmejoraMod);
            $actividades_accionmejora->setFechamod(new \DateTime('now'));
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
        }

        return $this->render('Calidad\accionesmejora\nuevaActividadPlan.html.twig', array('form' => $FORMA->createView(), 'registro' => $registro));
    }

    public function eliminarActividad($id, $idAccion) {

        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->find($id);
        try {
            $bd->remove($record);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha eliminado exitosamente!');
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'El registro no ha podido eliminarse!');
        }

        return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
    }

    public function solicitarAprobacion($idAccion, servNotificaciones $notifica) {

        $bd = $this->getDoctrine()->getManager();
        //$accionmejora = new accionesmejora();
        $idCompania = $this->getUser()->getIdCompania();
        $accionmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $accionmejora->setIdEstado(3);
        $Datosgestor = $bd->getRepository('App\Entity\Central\usuarios')->findOneBy(array('rol' => 'ROLE_ADMIN', 'activo' => 'TRUE', 'idcompania' => $idCompania), array('id' => 'DESC'));
        //dump($Datosgestor->getMail());exit;
        $CorreoGestor[] = $Datosgestor->getId();
 
        $microp = "";
        if ($accionmejora->getMicroprocesoxAccionesmejora() != null) {
            $microp = $accionmejora->getMicroprocesoxAccionesmejora()->getMicroproceso();
        }
        if (count($CorreoGestor) > 0) {
           
            $not1 = $notifica->crearNotificacion($CorreoGestor, 'Solicitud de aprobación de acción de mejora', 
                    'Se ha solicitado la aprobación de la siguiente acción de mejora:<br>
                    <div class="container" >
                        <div class="row">
                            <div class=col-md-2>Proceso:</div> 
                            <div class="col-md-3">'.$accionmejora->getProcesoxAccionesmejora()->getProceso().'</div>
                        </div>
                        <div class="row">
                            <div class=col-md-2>Subroceso:</div> 
                            <div class="col-md-3">'.$accionmejora->getSubprocesoxAccionesmejora()->getSubproceso().'</div>
                        </div>
                        <div class="row">
                            <div class=col-md-2>Microproceso:</div> 
                            <div class="col-md-3">'.$microp.'</div>
                        </div>
                         <div class="row">
                            <div class=col-md-2>Tipo de Accion:</div> 
                            <div class="col-md-3">'.$accionmejora->getTipoAccionMejoraxAccionesmejora()->getTipoAccionmejora().'</div>
                        </div>
                         <div class="row">
                            <div class=col-md-2>Orígen:</div> 
                            <div class="col-md-3">'.$accionmejora->getOrigenAccionMejoraxAccionesmejora()->getOrigenAccionmejora().'</div>
                        </div>
                        <div class="row">
                            <div class=col-md-2>Descripción:</div> 
                            <div class="col-md-3">'.$accionmejora->getDescripccion().'</div>
                        </div>
                         <div class="row">
                            <div class=col-md-2>Objetivo:</div> 
                            <div class="col-md-3">'.$accionmejora->getObjetivo().'</div>
                        </div>
                    </div>', 
                    'acciones_mejora_ver', 
                    1, 
                    'idAccion:' . $accionmejora->getId() . '');
        }
        $bd->flush();

        return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
    }

    public function aprobacionPlan(Request $request, $idAccion, servNotificaciones $notifica) {

        $bd = $this->getDoctrine()->getManager();
        //$accionmejora = new accionesmejora();
        //$accionmejora->setUsuariosxAccionesmejoraVobo()
        $accionmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $FORMA = $this->createFormBuilder($accionmejora, array(
                    'method' => 'POST', 'action' => $this->generateUrl('aprobacion_plan', array('idAccion' => $idAccion))))
                ->add('aprobacion', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array('Aprueba Plan de Acción' => 1, 'Rechaza Plan de Acción' => 0)))
                ->add('notavobo', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Aprobación/Rechazo'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();

        $FORMA->handleRequest($request);
        $actividades = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->findBy(array('idAccionmejora' => $idAccion));
        $arrayUsu=[];
        $arrayUsu[] = $accionmejora->getUsuariosxAccionesmejora()->getId();
        if ($FORMA->isValid()) {
            
            $acepta = $FORMA->getData()->getAprobacion();
            if ($acepta == 1) {
                $accionmejora->setIdEstado(4);
                $accionmejora->setFechavobo(new \DateTime('now'));
                $accionmejora->setUsuariosxAccionesmejoraVobo($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));
                $bd->flush();
                if ($actividades) {

                    foreach ($actividades as $value) {
                        $idResponsable = $value->getIdResponsable();
                        $usuario = $bd->getRepository('App\Entity\Central\usuarios')->find($idResponsable);
                        $actividadesEmail[] = $usuario->getId();
          
                        if ($actividades) {
                            
                            $not1 = $notifica->crearNotificacion($actividadesEmail, 
                            'Cordial Saludo', 
                            'Este mensaje indica la aprobación del plan de mejora que corresponde:<br>
                            <div class="container" >
                                <div class="row">
                                    <div class=col-md-2>Actividad:</div> 
                                    <div class="col-md-3">'.$value->getActividad().'</div>
                                </div>
                                <div class="row">
                                    <div class=col-md-2>Tratamiento:</div> 
                                    <div class="col-md-3">'.$value->getTratamiento().'</div>
                                </div>
                                <div class="row">
                                    <div class=col-md-2>Lugar:</div> 
                                    <div class="col-md-3">'.$value->getLugar().'</div>
                                </div>
                            </div>', 
                            'acciones_mejora_ver', 
                            1, 
                            'idAccion:' . $idAccion . '');
                        }
                    }
                }

                $microp = "";
                if ($accionmejora->getMicroprocesoxAccionesmejora() != null) {
                    $microp = $accionmejora->getMicroprocesoxAccionesmejora()->getMicroproceso();
                }
                $not1 = $notifica->crearNotificacion($arrayUsu, 
                'Cordial Saludo', 
                'este mensaje indica que la accion de mejora fue aprobada</strong>:<br>
                 <div class="container" >
                    <div class="row">
                        <div class=col-md-2>Proceso:</div> 
                        <div class="col-md-3">'.$accionmejora->getProcesoxAccionesmejora()->getProceso().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Subproceso:</div> 
                        <div class="col-md-3">'.$accionmejora->getSubprocesoxAccionesmejora()->getSubproceso().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Microproceso:</div> 
                        <div class="col-md-3">'.$microp.'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Tipo de Accion:</div> 
                        <div class="col-md-3">'.$accionmejora->getTipoAccionMejoraxAccionesmejora()->getTipoAccionmejora().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Orígen:</div> 
                        <div class="col-md-3">'.$accionmejora->getOrigenAccionMejoraxAccionesmejora()->getOrigenAccionmejora().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Descripción:</div> 
                        <div class="col-md-3">'.$accionmejora->getDescripccion().'</div>
                    </div>
                     <div class="row">
                        <div class=col-md-2>Objetivo:</div> 
                        <div class="col-md-3">'.$accionmejora->getObjetivo().'</div>
                    </div>
                </div>', 
                'acciones_mejora_ver', 
                1, 
                'idAccion:' . $value->getId() . '');

                return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
            } else {

                $accionmejora->setIdEstado(6);

                $bd->flush();
                $microp = "";
                if ($accionmejora->getMicroprocesoxAccionesmejora() != null) {
                    $microp = $accionmejora->getMicroprocesoxAccionesmejora()->getMicroproceso();
                }
                $not1 = $notifica->crearNotificacion($arrayUsu, 
                'Cordial Saludo', 
                'este mensaje indica que la accion de mejora fue rechazada</strong>:<br>
                 <div class="container" >
                    <div class="row">
                        <div class=col-md-2>Proceso:</div> 
                        <div class="col-md-3">'.$accionmejora->getProcesoxAccionesmejora()->getProceso().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Subproceso:</div> 
                        <div class="col-md-3">'.$accionmejora->getSubprocesoxAccionesmejora()->getSubproceso().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Microproceso:</div> 
                        <div class="col-md-3">'.$microp.'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Tipo de Accion:</div> 
                        <div class="col-md-3">'.$accionmejora->getTipoAccionMejoraxAccionesmejora()->getTipoAccionmejora().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Orígen:</div> 
                        <div class="col-md-3">'.$accionmejora->getOrigenAccionMejoraxAccionesmejora()->getOrigenAccionmejora().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Descripción:</div> 
                        <div class="col-md-3">'.$accionmejora->getDescripccion().'</div>
                    </div>
                     <div class="row">
                        <div class=col-md-2>Objetivo:</div> 
                        <div class="col-md-3">'.$accionmejora->getObjetivo().'</div>
                    </div>
                </div>', 
                'acciones_mejora_ver', 
                1, 
                'idAccion:' . $accionmejora->getId() . '');

                return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
            }
        }

        return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
    }

    public function vistoBuenoActividad(Request $request, $id, $idAccion, servNotificaciones $notifica) {

        $bd = $this->getDoctrine()->getManager();
        //$accionmejora = new accionesmejora();
        $accionmejora = $bd->getRepository('App\Entity\Calidad\accionesmejora')->find($idAccion);
        $ultimaActividad = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora');
        $ActividadD = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->findBy(array('idAccionmejora' => $idAccion));
        $idCompania = $this->getUser()->getIdCompania();
        // dump($ActividadD);exit;
        if (count($ActividadD) > 1) {
            $query = $ultimaActividad->createQueryBuilder('u')
                    ->where('u.idAccionmejora=:id')
                    ->andWhere('u.idUsuariocierra is null')
                    ->setParameter('id', $idAccion)
                    ->getQuery();
            $actividades = $query->getResult();
            $cont = count($actividades);
        } elseif (count($ActividadD) == 1) {
            $query = $ultimaActividad->createQueryBuilder('u')
                    ->where('u.idAccionmejora=:id')
                    ->andWhere('u.acepta = true')
                    ->setParameter('id', $idAccion)
                    ->getQuery();
            $actividades = $query->getResult();
            $cont = count($actividades);
        } else {
            $cont = 0;
        }

        $actividad = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->find($id);
        $FORMA = $this->createFormBuilder($actividad, array(
                    'method' => 'POST', 'action' => $this->generateUrl('actividad_visto_bueno', array('id' => $id, 'idAccion' => $idAccion))))
                ->add('acepta', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array('Aprueba Plan de Acción' => true, 'Rechaza Plan de Acción' => false)))
                ->add('notacierre', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Nota de Aprobación/Rechazo'))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();

        $FORMA->handleRequest($request);

        if ($FORMA->isValid()) {

            //$actividades = new actividades_accionmejora();
            $usuariosxActividadesAccionmejoraCierra = $bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId());
            $actividad->setUsuariosxActividadesAccionmejoraCierra($usuariosxActividadesAccionmejoraCierra);
            $actividad->setFechacierra(new \DateTime('now'));
            $bd->flush();

            $idResponsable = $actividad->getIdResponsable();
            $usuario = $bd->getRepository('App\Entity\Central\usuarios')->find($idResponsable);
            $actividadesEmail[] = $usuario->getId();
            $acepta = $FORMA->getData()->getAcepta();

            if ($acepta == true) {
                $resp = "aprobada";
            } else {
                $resp = "rechazada";
            }
            
            $not1 = $notifica->crearNotificacion($actividadesEmail, 
            'Cordial Saludo', 
            'Este mensaje indica que la siguiente  actividad ha sido '.$resp.':<br>
            <div class="container" >
                <div class="row">
                    <div class=col-md-2>Actividad:</div> 
                    <div class="col-md-3">'.$actividad->getActividad().'</div>
                </div>
                <div class="row">
                    <div class=col-md-2>Tratamiento:</div> 
                    <div class="col-md-3">'.$actividad->getTratamiento().'</div>
                </div>
                <div class="row">
                    <div class=col-md-2>Lugar:</div> 
                    <div class="col-md-3">'.$actividad->getLugar().'</div>
                </div>
            </div>', 
            'actividad_ver', 
            1, 
            'idAccion:'.$idAccion. ',idActividad:'.$actividad->getId().',ban:0');

            if ($cont == 1 && $acepta == true) {

                $estadol = 2;
                $accionmejora->setIdEstado(5);

                //Pacho: Este bloque se utiliza para cambiar el estado de la noconformidad relacionada a 7
                $noconformes = $bd->getRepository('App\Entity\Calidad\\noconformes')->findBy(array('idAccionmejora' => $idAccion));

                if ($noconformes) {
                    foreach ($noconformes as $noconfor) {
                        $noconfor->setEstadoxNoconformes($bd->getRepository('App\Entity\Calidad\estados_noconformidad')->find(7));
                    }
                }
                //Fin bloque 

                $bd->flush();

                $Datosgestor = $bd->getRepository('App\Entity\Central\usuarios')->findOneBy(array('rol' => 'ROLE_ADMIN', 'activo' => 'TRUE', 'idcompania' => $idCompania), array('id' => 'DESC'));
                //dump($Datosgestor->getMail());exit;
                $CorreoGestor[] = $Datosgestor->getId();
                $microp ="";
                if ($accionmejora->getMicroprocesoxAccionesmejora() != null) {
                   $microp = $accionmejora->getMicroprocesoxAccionesmejora()->getMicroproceso();
                }
                $not1 = $notifica->crearNotificacion($CorreoGestor, 
                'Cordial Saludo', 
                'Este mensaje indica la finalización satifactoria del plan de acción correspondiente a la acción de mejora:<br>
                 <div class="container" >
                    <div class="row">
                        <div class=col-md-2>Proceso:</div> 
                        <div class="col-md-3">'.$accionmejora->getProcesoxAccionesmejora()->getProceso().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Subproceso:</div> 
                        <div class="col-md-3">'.$accionmejora->getSubprocesoxAccionesmejora()->getSubproceso().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Microproceso:</div> 
                        <div class="col-md-3">'.$microp.'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Tipo de Accion:</div> 
                        <div class="col-md-3">'.$accionmejora->getTipoAccionMejoraxAccionesmejora()->getTipoAccionmejora().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Orígen:</div> 
                        <div class="col-md-3">'.$accionmejora->getOrigenAccionMejoraxAccionesmejora()->getOrigenAccionmejora().'</div>
                    </div>
                    <div class="row">
                        <div class=col-md-2>Descripción:</div> 
                        <div class="col-md-3">'.$accionmejora->getDescripccion().'</div>
                    </div>
                     <div class="row">
                        <div class=col-md-2>Objetivo:</div> 
                        <div class="col-md-3">'.$accionmejora->getObjetivo().'</div>
                    </div>
                </div>', 
                'actividad_ver', 
                1, 
                'idAccion:'.$idAccion. ',idActividad:'.$actividad->getId().',ban:0');

            }

            return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
        }

        return $this->redirectToRoute('plan_accion_mejora', array('idAccion' => $idAccion));
    }

    public function guardarSeguimientoActividad(Request $request, $idAccion, $idActividad, $ban) {

        $bd = $this->getDoctrine()->getManager();
        $seguimiento_actividad = new seguimiento_actividad();
        $actividad = $bd->getRepository('App\Entity\Calidad\actividades_accionmejora')->find($idActividad);
        $FORMA2 = $this->createFormBuilder($seguimiento_actividad, array(
                    'method' => 'POST', 'action' => $this->generateUrl('seguimiento_actividad_guardar', array('idAccion' => $idAccion, 'idActividad' => $idActividad, 'ban' => $ban))))
                ->add('fechacre', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array('widget' => 'single_text'))
                ->add('nota', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, array('label' => 'Descripción'))
                ->add('anexo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, array('data_class' => null))
                ->add('solicitaCierre', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array('label' => 'Solicitar Cierre',
                    'choices' => array('Si' => true, 'No' => false)))
                ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Aceptar'))
                ->getForm();
        $FORMA2->handleRequest($request);
        $acepta = $FORMA2->getData()->getSolicitaCierre();
        if ($FORMA2->isValid()) {

            $seguimiento_actividad->setActividadesAccionmejoraxseguimientoActividad($actividad);
            $seguimiento_actividad->setFechacre(new \DateTime('now'));
            $seguimiento_actividad->setUsuariosxSeguimientoActividad($bd->getRepository('App\Entity\Central\usuarios')->find($this->getUser()->getId()));

            $file = $seguimiento_actividad->getAnexo();
            if ($file) {
                $nombreArchivo = $file->getClientOriginalName();
                $seguimiento_actividad->setNombreArchivo($nombreArchivo);
                $imagen = $file;//->getData();
                $documentos = $this->getDoctrine()->getManager('documentos');
                $anexo = new \App\Entity\Documentos\Documentos();
                $anexo->setDocumento(base64_encode(file_get_contents($imagen)));
                $anexo->setExt($imagen->guessExtension());
                $documentos->persist($anexo);
                $documentos->flush();
                $idAnexo = $anexo->getId();
                $seguimiento_actividad->setAnexo($idAnexo);
            }
            $bd->persist($seguimiento_actividad);
            $actividad->setAcepta($acepta);
            $bd->flush();
            $this->addFlash('mensaje', 'El registro se ha guardado exitosamente!');

            return $this->redirectToRoute('actividad_ver', array('idAccion' => $idAccion, 'idActividad' => $idActividad, 'ban' => $ban));
        }

        return $this->redirectToRoute('actividad_ver', array('idAccion' => $idAccion,
                    'idActividad' => $idActividad, 'ban' => $ban));
    }

    public function eliminarSeguimientoActividad($idAccion, $idActividad, $id) {

        $bd = $this->getDoctrine()->getManager();
        $record = $bd->getRepository('App\Entity\Calidad\seguimiento_actividad')->find($id);
        $bd->remove($record);
        $bd->flush();

        return $this->redirectToRoute('actividad_ver', array('idAccion' => $idAccion, 'idActividad' => $idActividad));
    }

    public function actividesMejora(Request $request, PaginatorInterface $paginator) {

        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $idUsuario = $this->getUser()->getId();
        if ($request->request->get('form')) {
            $valores = $request->request->get('form');

            array_pop($valores);
            foreach ($valores as $valor) {
                if ($valor == 0) {

                    $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                            . "a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c"
                            . " where c.idCompania=:id and b.idEstado>=4 and a.idResponsable=" . $idUsuario . "";
                    $query = $bd->createQuery($dql);
                }

                if ($valor == 1) {

                    $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                            . "a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c"
                            . " where c.idCompania=:id and b.idEstado = 5 and b.fechacierra is not null and a.idResponsable=" . $idUsuario . "";
                    $query = $bd->createQuery($dql);
                }
                if ($valor == 2) {

                    $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                            . "a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c"
                            . " where c.idCompania=:id and a.acepta =FALSE and a.idResponsable=" . $idUsuario . " and b.idEstado=4";
                    $query = $bd->createQuery($dql);
                }
                if ($valor == 3) {
                    $ban3 = 3;
                    $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                            . " a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c "
                            . " LEFT JOIN a.seguimientoActividadxActividadesAccionmejora d"
                            . " where c.idCompania=:id and d.id is null and a.idResponsable=" . $idUsuario . " and b.idEstado=4";
                    $query = $bd->createQuery($dql);
                }
                if ($valor == 4) {
                    $ban4 = 4;
                    $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                            . " a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c "
                            . " LEFT JOIN a.seguimientoActividadxActividadesAccionmejora d"
                            . " where c.idCompania=:id and d.id is not null and a.idResponsable=" . $idUsuario . " and b.idEstado=4";
                    $query = $bd->createQuery($dql);
                }
                if ($valor == 5) {

                    $fecha = new \DateTime('+1 day');
                    $anio = $fecha->format('Y');
                    $mes = $fecha->format('m');
                    $dia = $fecha->format('d');
                    $fec = $anio . "-" . $mes . "-" . $dia;
                    $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                            . "a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c"
                            . " where c.idCompania=:id and a.acepta is null and a.fechaLimite<'" . $fec . "' and a.idResponsable=" . $idUsuario . " and b.idEstado=4";
                    $query = $bd->createQuery($dql);
                }
            }
        } else {
            $dql = "select a from App\Entity\Calidad\actividades_accionmejora a JOIN "
                    . "a.accionesmejoraxActividadesAccionmejora b JOIN b.procesoxAccionesmejora c"
                    . " where c.idCompania=:id and b.idEstado>=4 and a.idResponsable=" . $idUsuario . "";
            $query = $bd->createQuery($dql);
        }
        $query->setParameter('id', $idCompania);
        $registro = $query->getResult();

        $FORMA = $this->createFormBuilder($registro, array(
                    'method' => 'POST', 'action' => $this->generateUrl('actividades_mejora')))
                ->add('estado', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
                    'choices' => array(
                        'todos' => '0',
                        'Actividades de acciones de mejora cerradas' => '1',
                        'Actividades rechazadas' => '2',
                        'Actividades sin seguimiento' => '3',
                        'Actividades con seguimiento' => '4',
                        'Actividades por vencerse' => '5')
                ))
                //->add('filtrar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array('label' => 'Ver'))
                ->getForm();
        $FORMA->handleRequest($request);
//        if ($FORMA->isValid()) {
//            
//        }
        if (empty($valores)) {
            $valores = "";
        }
        
        $pagination = $paginator->paginate($registro, $request->query->getInt('page', 1), 20);
        return $this->render('Calidad\accionesmejora\actividadesMejora.html.twig', array('form' => $FORMA->createView(), 'registro' => $pagination, 'valores' => $valores));
    }

}
