<?php

namespace App\Controller\Calidad;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;

class archivoHistoricoController extends AbstractController {

    public function archivoHistoricoListar(Request $request, PaginatorInterface $paginator) {
        $bd = $this->getDoctrine()->getManager();
        $idCompania = $this->getUser()->getIdCompania();
        $UserRepository = $bd->getRepository(\App\Entity\Central\usuarios::class)->find($this->getUser()->getId());
        $NoRadicado = '';
        $asunto = '';
        $tipo = '';
        $remitente = '';
        $fechaI = '';
        $fechaF = '';

        $valoresFiltros = $request->query->get('form');

        if (!empty($valoresFiltros['id'])) {
            $NoRadicado = $valoresFiltros['id'];
        }
        if (!empty($valoresFiltros['asunto'])) {
            $asunto = $valoresFiltros['asunto'];
        }
        if (!empty($valoresFiltros['tipoxCorrespondencia'])) {
            $tipo = $valoresFiltros['tipoxCorrespondencia'];
        }
        if (!empty($valoresFiltros['remitente'])) {
            $remitente = $valoresFiltros['remitente'];
        }
        if (!empty($valoresFiltros['fechaI'])) {
            $fechaI = $valoresFiltros['fechaI'];
        }
        if (!empty($valoresFiltros['fechaF'])) {
            $fechaF = $valoresFiltros['fechaF'];
        }

        $archivoHistorico = $this->getDoctrine()
                ->getRepository(\App\Entity\Calidad\archivohistorico::class)
                ->findFiltros($idCompania, $NoRadicado, $asunto, $tipo, $remitente, $fechaI, $fechaF);

        $FORMA1 = $this->createFormBuilder($archivoHistorico, array(
                    'csrf_protection' => false,
                    'method' => 'GET', 'action' => $this->generateUrl('archivo_historico')))
                ->add('fechaI', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array(
                    'label' => 'Desde', 'widget' => 'single_text'))
                ->add('fechaF', \Symfony\Component\Form\Extension\Core\Type\DateType::class, array(
                    'label' => 'Hasta', 'widget' => 'single_text'))
                ->add('remitente', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
                    'label' => 'Remitente'))
                ->add('tipoxCorrespondencia', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                    'class' => 'App\Entity\Calidad\tiposDoc',
                    'choice_value' => 'id',
                    'choice_label' => 'tipo',
                    'placeholder' => 'Seleccione el tipo de documento',
                    'label' => 'Tipo Documento:',
                    'query_builder' => function(\App\Repository\Calidad\tiposDocRepository $er) use ($idCompania) {
                        return $er->createQueryBuilder('w')
                                ->where('w.idCompania = :a')
                                ->setParameter('a', $idCompania);
                    },
                ))
                ->add('id', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, array('label' => '#Radicado'))
                ->add('asunto', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array('label' => 'Asunto'))
                ->getForm();

        $pagination = $paginator->paginate($archivoHistorico, $request->query->getInt('page', 1), 15);

        $archivohistorico = new \App\Entity\Calidad\archivohistorico();
        $FORMA = $this->createForm(\App\Form\Calidad\archivohistoricoType::class, $archivohistorico, array(
            'method' => 'POST', 'idCompania' => $idCompania,
            'action' => $this->generateUrl('archivo_historico')));
        $FORMA->handleRequest($request);
        if ($FORMA->isSubmitted() and $FORMA->isValid()) {

            $imagen = $FORMA['ruta_anexo']->getData();
            $size = filesize($imagen);
            if ($size > 5000000) {
                $this->addFlash('error', 'El tamaño del archivo no debe ser superior a 5Mb.!');
                return $this->redirectToRoute('archivo_historico');
            }
            $ext = $imagen->guessExtension();
            $arrayExt = array('PNG', 'JPG', 'JPEG', 'GIF', 'PDF');
            if (!in_array(strtoupper($ext), $arrayExt)) {
                $this->addFlash('error', 'El tipo de archivo no es válido.!');
                return $this->redirectToRoute('archivo_historico');
            }
            $documentos = $this->getDoctrine()->getManager('documentos');
            $anexo = new \App\Entity\Documentos\Documentos();
            $anexo->setDocumento(base64_encode(file_get_contents($imagen)));
            $anexo->setExt($imagen->guessExtension());
            $documentos->persist($anexo);
            $documentos->flush();
            $idAnexo = $anexo->getId();

            $archivohistorico->setRutaAnexo($idAnexo);
            $archivohistorico->setUsuarioCreaxArchivohistorico($UserRepository);
            $archivohistorico->setFechaCrea(new \DateTime('now'));
            $bd->persist($archivohistorico);
            $bd->flush();

            return $this->redirectToRoute('archivo_historico');
        }

        return $this->render('Calidad/archivoHistorico/archivoHistorico.html.twig', array(
                    'registro' => $pagination, 'form' => $FORMA->createView(), 'form1' => $FORMA1->createView()));
    }

    public function verArchivoHistorico($id) {
        $bd = $this->getDoctrine()->getManager();
        $archivohistorico = $bd->getRepository(\App\Entity\Calidad\archivohistorico::class)->find($id);
        $listaAnexos = $this->getAnexos($archivohistorico);

        $FORM = $this->getFormArchivoHistorico($id);
        $FORMSALIDA = $this->getFormSalidasParciales($id);
        $FORMDEVOLUCION = $this->getFormDevolucion($id);

        return $this->render('Calidad/archivoHistorico/verArchivoHistorico.html.twig', array(
                    'reg' => $archivohistorico,
                    'anexos' => $listaAnexos,
                    'form' => $FORM->createView(),
                    'formSalida' => $FORMSALIDA->createView(),
                    'formDevolucion' => $FORMDEVOLUCION->createView()
        ));
    }

    public function actualizarSalida($id, Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $archivohistorico = $bd->getRepository(\App\Entity\Calidad\archivohistorico::class)->find($id);

        $indice = count($archivohistorico->getSalidaxArchivohistorico()) - 1;
        $salida = new \App\Entity\Calidad\salidasParciales();
        $salida = $archivohistorico->getSalidaxArchivohistorico()[$indice];
        $salida->setEstado(2);
        $salida->setFechaEntra(new \DateTime('now'));
        $salida->setNotaDevolucion($request->get('form')['notaDevolucion']);
        $bd->persist($salida);
        $bd->flush();
        return $this->redirectToRoute('ver_archivo_historico', array('id' => $id));
    }

    public function guardarSalida($id, Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $archivohistorico = $bd->getRepository(\App\Entity\Calidad\archivohistorico::class)->find($id);
        $salida = new \App\Entity\Calidad\salidasParciales();
        $salida->setFechaSale(new \DateTime('now'));
        $salida->setEstado(1);
        $salida->setUsuario($request->get('form')['usuario']);
        $salida->setJustificacion($request->get('form')['justificacion']);
        $salida->setArchivohistoricoxSalida($archivohistorico);
        $bd->persist($salida);
        $bd->flush();
        return $this->redirectToRoute('ver_archivo_historico', array('id' => $id));
    }

    public function guardarTraslado($id, Request $request) {
        $bd = $this->getDoctrine()->getManager();
        $archivohistorico = $bd->getRepository(\App\Entity\Calidad\archivohistorico::class)->find($id);

        if ($archivohistorico->getUbicacionxArchivohistorico()) {//----------------Es obligatorio que exista por lo menos la ubicación DD
            $this->addFlash('error', 'No existe una ubicación');
            return $this->redirectToRoute('ver_archivo_historico', array('id' => $id));
        }

        if (count($archivohistorico->getTrasladoxarchivohistorico()) == 0) {
            $traslado1 = new \App\Entity\Calidad\traslados();
            $traslado1->setArchivohistoricoxTraslado($archivohistorico);
            $traslado1->setUsuarioCreaxTraslado($archivohistorico->getUsuarioCreaxArchivohistorico());
            $traslado1->setFecha($archivohistorico->getFecha());
            $traslado1->setUbicacionxTraslado($archivohistorico->getUbicacionxArchivohistorico());
            $traslado1->setSububicacionxTraslado($archivohistorico->getSububicacionxArchivohistorico());
            $traslado1->setOficinaxTraslado($archivohistorico->getOficinaxArchivohistorico());
            $traslado1->setEstantexTraslado($archivohistorico->getEstantexArchivohistorico());
            $traslado1->setNivelxTraslado($archivohistorico->getNivelxArchivohistorico());
            $traslado1->setCeldaxTraslado($archivohistorico->getCeldaxArchivohistorico());
            $bd->persist($traslado1);
        }

        $traslado = new \App\Entity\Calidad\traslados();
        $traslado->setArchivohistoricoxTraslado($archivohistorico);
        $traslado->setUsuarioCreaxTraslado($this->getUser());
        $traslado->setFecha(new \DateTime('now'));

        if (!empty($request->get('form')['ubicacionxTraslado']) and $request->get('form')['ubicacionxTraslado'] != null) {
            $ubicacion = $bd->getRepository(\App\Entity\Calidad\trdubicacion::class)->find($request->get('form')['ubicacionxTraslado']);
            $traslado->setUbicacionxTraslado($ubicacion);
            $archivohistorico->setUbicacionxArchivohistorico($ubicacion);
        } else {//----------------------------------------Es obligatorio que exista por lo menos la ubicación DD
            $this->addFlash('error', 'No existe una ubicación');
            return $this->redirectToRoute('ver_archivo_historico', array('id' => $id));
        }
        if (!empty($request->get('form')['sububicacionxTraslado']) and $request->get('form')['sububicacionxTraslado'] != null) {
            $sububicacion = $bd->getRepository(\App\Entity\Calidad\trdsububicacion::class)->find($request->get('form')['sububicacionxTraslado']);
            $traslado->setSububicacionxTraslado($sububicacion);
            $archivohistorico->setSububicacionxArchivohistorico($sububicacion);
        } else {
            $traslado->setSububicacionxTraslado(null);
            $archivohistorico->setSububicacionxArchivohistorico(null);
        }
        if (!empty($request->get('form')['oficinaxTraslado']) and $request->get('form')['oficinaxTraslado'] != null) {
            $oficina = $bd->getRepository(\App\Entity\Calidad\trdoficina::class)->find($request->get('form')['oficinaxTraslado']);
            $traslado->setOficinaxTraslado($oficina);
            $archivohistorico->SetOficinaxArchivohistorico($oficina);
        } else {
            $traslado->setOficinaxTraslado(null);
            $archivohistorico->SetOficinaxArchivohistorico(null);
        }
        if (!empty($request->get('form')['estantexTraslado']) and $request->get('form')['estantexTraslado'] != null) {
            $estante = $bd->getRepository(\App\Entity\Calidad\trdestante::class)->find($request->get('form')['estantexTraslado']);
            $traslado->setEstantexTraslado($estante);
            $archivohistorico->setEstantexArchivohistorico($estante);
        } else {
            $traslado->setEstantexTraslado(null);
            $archivohistorico->setEstantexArchivohistorico(null);
        }
        if (!empty($request->get('form')['nivelxTraslado']) and $request->get('form')['nivelxTraslado'] != null) {
            $nivel = $bd->getRepository(\App\Entity\Calidad\trdnivel::class)->find($request->get('form')['nivelxTraslado']);
            $traslado->setNivelxTraslado($nivel);
            $archivohistorico->setNivelxArchivohistorico($nivel);
        } else {
            $traslado->setNivelxTraslado(null);
            $archivohistorico->setNivelxArchivohistorico(null);
        }
        if (!empty($request->get('form')['nivelxTraslado']) and $request->get('form')['nivelxTraslado'] != null) {
            $celda = $bd->getRepository(\App\Entity\Calidad\trdcelda::class)->find($request->get('form')['nivelxTraslado']);
            $traslado->setCeldaxTraslado($celda);
            $archivohistorico->setCeldaxArchivohistorico($celda);
        } else {
            $traslado->setCeldaxTraslado(null);
            $archivohistorico->setCeldaxArchivohistorico(null);
        }
        $bd->persist($traslado);
        $bd->flush();

        return $this->redirectToRoute('ver_archivo_historico', array('id' => $id));
    }

    public function archivoHistoricoEliminar($id) {

        try {
            $bd = $this->getDoctrine()->getManager();
            $record = $bd->getRepository(\App\Entity\Calidad\archivohistorico::class)->find($id);
            foreach ($record->getSalidaxArchivohistorico() as $r) {
                $bd->remove($r);
            }
            foreach ($record->getTrasladoxArchivohistorico() as $r) {
                $bd->remove($r);
            }
            $bd->remove($record);
            $bd->flush();
        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            $this->addFlash('error', 'archivo historico en uso!');
        }

        return $this->redirectToRoute('archivo_historico');
    }

    private function getFormArchivoHistorico($id) {
        $idCompania = $this->getUser()->getIdCompania();
        return $this->createFormBuilder(null, array(
                            'method' => 'POST',
                            'action' => $this->generateUrl('traslado_archivo_guardar', array('id' => $id))))
                        ->add('ubicacionxTraslado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                            'class' => \App\Entity\Calidad\trdubicacion::class,
                            'choice_value' => 'id',
                            'choice_label' => 'ubicacion',
                            'label' => 'Ubicación',
                            'placeholder' => 'Seleccione una ubicación',
                            'required' => true,
                            'query_builder' => function(\App\Repository\Calidad\trdubicacionRepository $er) use ($idCompania) {
                                return $er->createQueryBuilder('w')
                                        ->where('w.idCompania = :a')
                                        ->setParameter('a', $idCompania);
                            },
                        ))
                        ->add('sububicacionxTraslado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                            'class' => \App\Entity\Calidad\trdsububicacion::class,
                            'choice_value' => 'id',
                            'choice_label' => 'sububicacion',
                            'label' => 'SubUbicación',
                            'required' => false,
                            'placeholder' => 'Seleccione una sububicación',
                        ))
                        ->add('oficinaxTraslado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                            'class' => \App\Entity\Calidad\trdoficina::class,
                            'choice_value' => 'id',
                            'choice_label' => 'oficina',
                            'label' => 'Oficina',
                            'required' => false,
                            'placeholder' => 'Seleccione una oficina',
                        ))
                        ->add('estantexTraslado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                            'class' => \App\Entity\Calidad\trdestante::class,
                            'choice_value' => 'id',
                            'choice_label' => 'estante',
                            'label' => 'Estante',
                            'required' => false,
                            'placeholder' => 'Seleccione una oficina',
                        ))
                        ->add('nivelxTraslado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                            'class' => \App\Entity\Calidad\trdnivel::class,
                            'choice_value' => 'id',
                            'choice_label' => 'nivel',
                            'label' => 'Nivel',
                            'required' => false,
                            'placeholder' => 'Seleccione un estante',
                        ))
                        ->add('celdaxTraslado', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, array(
                            'class' => \App\Entity\Calidad\trdcelda::class,
                            'choice_value' => 'id',
                            'choice_label' => 'celda',
                            'label' => 'Celda',
                            'required' => false,
                            'placeholder' => 'Seleccione una celda',
                        ))
                        ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array(
                            'label' => 'Guardar'))
                        ->getForm();
    }

    private function getFormSalidasParciales($id) {
        return $this->createFormBuilder(null, array(
                            'method' => 'POST',
                            'action' => $this->generateUrl('salida_parcial_guardar', array('id' => $id))))
                        ->add('usuario', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
                            'label' => 'Usuario',
                            'required' => true,
                        ))
                        ->add('justificacion', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
                            'label' => 'Justificación',
                            'required' => true,
                        ))
                        ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array(
                            'label' => 'Guardar'))
                        ->getForm();
    }

    private function getFormDevolucion($id) {
        return $this->createFormBuilder(null, array(
                            'method' => 'POST',
                            'action' => $this->generateUrl('salida_parcial_actualizar', array('id' => $id))))
                        ->add('notaDevolucion', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
                            'label' => 'Nota de devolución',
                            'required' => true,
                        ))
                        ->add('guardar', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, array(
                            'label' => 'Guardar'))
                        ->getForm();
    }

    private function getAnexos($archivohistorico) {
        $arrayAnexos = [];
        if ($archivohistorico->getCorrespondenciaxArchivohistorico()) {
            foreach ($archivohistorico->getCorrespondenciaxArchivohistorico()->getAnexosxCorrespondencia() as $anexo) {
                $arrayAnexos[] = $anexo->getRuta();
            }
        } else {
            $arrayAnexos[] = $archivohistorico->getRutaAnexo();
        }

        $listaAnexos = [];
        $dbDoc = $this->getDoctrine()->getManager('documentos');
        foreach ($arrayAnexos as $id) {
            $imagen = $dbDoc->createQueryBuilder()
                            ->select('imagen')
                            ->from(\App\Entity\Documentos\Documentos::class, 'imagen')
                            ->where('imagen.id=:ID')
                            ->setParameter(':ID', $id)
                            ->getQuery()->getOneOrNullResult();

            if ($imagen != null) {
                $item = ['id' => $imagen->getId(), 'ext' => $imagen->getExt(), 'documento' => $imagen->getDocumento(), 'descripcion' => 'Archivo-' . $imagen->getId()];
                $listaAnexos[] = $item;
            }
        }
        return $listaAnexos;
    }

}
