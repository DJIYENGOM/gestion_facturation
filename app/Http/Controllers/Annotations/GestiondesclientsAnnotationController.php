<?php

namespace App\Http\Controllers\Annotations ;

/**
 * @OA\Security(
 *     security={
 *         "BearerAuth": {}
 *     }),

 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"),

 * @OA\Info(
 *     title="Your API Title",
 *     description="Your API Description",
 *     version="1.0.0"),

 * @OA\Consumes({
 *     "multipart/form-data"
 * }),

 *

 * @OA\DELETE(
 *     path="/api/supprimerClient/{id}",
 *     summary="supprimer Client",
 *     description="",
 *         security={
 *    {       "BearerAuth": {}}
 *         },
 * @OA\Response(response="204", description="Deleted successfully"),
 * @OA\Response(response="401", description="Unauthorized"),
 * @OA\Response(response="403", description="Forbidden"),
 * @OA\Response(response="404", description="Not Found"),
 *     @OA\Parameter(in="path", name="id", required=false, @OA\Schema(type="string")
 * ),
 *     @OA\Parameter(in="header", name="User-Agent", required=false, @OA\Schema(type="string")
 * ),
 *     tags={"gestion des clients"},
*),


 * @OA\GET(
 *     path="/api/listerClients",
 *     summary="lister clients",
 *     description="",
 *         security={
 *    {       "BearerAuth": {}}
 *         },
 * @OA\Response(response="200", description="OK"),
 * @OA\Response(response="404", description="Not Found"),
 * @OA\Response(response="500", description="Internal Server Error"),
 *     @OA\Parameter(in="header", name="User-Agent", required=false, @OA\Schema(type="string")
 * ),
 *     tags={"gestion des clients"},
*),


 * @OA\POST(
 *     path="/api/ajouterClient",
 *     summary="ajouter un Client",
 *     description="",
 *         security={
 *    {       "BearerAuth": {}}
 *         },
 * @OA\Response(response="201", description="Created successfully"),
 * @OA\Response(response="400", description="Bad Request"),
 * @OA\Response(response="401", description="Unauthorized"),
 * @OA\Response(response="403", description="Forbidden"),
 *     @OA\Parameter(in="header", name="User-Agent", required=false, @OA\Schema(type="string")
 * ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 properties={
 *                     @OA\Property(property="prenom_client", type="string"),
 *                     @OA\Property(property="nom_client", type="string"),
 *                     @OA\Property(property="email_client", type="string"),
 *                     @OA\Property(property="adress_client", type="string"),
 *                     @OA\Property(property="tel_client", type="string"),
 *                     @OA\Property(property="categorie_id", type="string"),
 *                     @OA\Property(property="nom_entreprise", type="string"),
 *                 },
 *             ),
 *         ),
 *     ),
 *     tags={"gestion des clients"},
*),


 * @OA\POST(
 *     path="/api/modifierClient/{id}",
 *     summary="modifier un Client",
 *     description="",
 *         security={
 *    {       "BearerAuth": {}}
 *         },
 * @OA\Response(response="201", description="Created successfully"),
 * @OA\Response(response="400", description="Bad Request"),
 * @OA\Response(response="401", description="Unauthorized"),
 * @OA\Response(response="403", description="Forbidden"),
 *     @OA\Parameter(in="path", name="id", required=false, @OA\Schema(type="string")
 * ),
 *     @OA\Parameter(in="header", name="User-Agent", required=false, @OA\Schema(type="string")
 * ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 properties={
 *                     @OA\Property(property="prenom_client", type="string"),
 *                     @OA\Property(property="nom_client", type="string"),
 *                     @OA\Property(property="email_client", type="string"),
 *                     @OA\Property(property="adress_client", type="string"),
 *                     @OA\Property(property="tel_client", type="string"),
 *                     @OA\Property(property="categorie_id", type="string"),
 *                     @OA\Property(property="nom_entreprise", type="string"),
 *                 },
 *             ),
 *         ),
 *     ),
 *     tags={"gestion des clients"},
*),


*/

 class GestiondesclientsAnnotationController {}
