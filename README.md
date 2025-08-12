Módulos que ya existen y NO deben tocarse en UI.

Módulos que debo crear o modificar (por ejemplo: “ABC Cursos” en maestro).

Librerías que ya usas (Bootstrap versión exacta, DataTables, CKEditor, etc.).

Rutas clave y estructura de menú.

📄 Plantilla de Documentación – Sistema Dojo
1. Objetivo del sistema
Sistema web con accesos a Admin, maestros y alumnos, que permita a los maestros dar de alta contenido como video de youtube, pdf, imagenes, o texto, permita crear grupos, grados y cursos, incluso crear escuelas.
2. Tipos de usuarios y roles
Lista de roles y sus permisos.
Rol	Descripción	Ver contenido	Editar datos	Puede asignar grados	
Admin	El admin tiene acceso a todo tanto en modo alumno(ver contenido) modo maestro, y el puede dar de alta a los maestros, destionar cursos, grupos etc.	si	si	si	
Maestro	 El maestro debe poder gestionar y restringir contnido al alumno, contenido por contenido. 
Asignar calificacion por mes, por cuatrimestre completado.
promover siguiente grado.
retro al alumno en formato de texto.
	si	si	si	
Alumno	Puede consultar contenido.
Adicional un panel de avance en formato de grafica de habilidades:
kihon, kata, kumitachi, tate, reiho, Teoria.
calificación por grado.	si	No (solo datos de perfil propios, nombre, email, telefono.	no	


3. Módulos / Funcionalidades
Para cada módulo, detalla:
Módulo: (ABC Alumnos)
- Objetivo: Gestionar alumnos y su estatus : activo/suspendido/baja
- Entradas: (nombre, apellido paterno, apellido materno, email,telefono,maestro, folio, vigencia, url_foto , asignar grado(kyu), asignar grupo, curp,)
- Procesos: Se guardan en la Base de datos, considerar la base datos de la tabla alumnos
- Salidas: usuario(email), password acceso al Sistema rol alumno.
- Restricciones: (reglas especiales)
Módulo: (ABC Maestros)
- Objetivo: gestionar datos de maestros activos
- Entradas: Entradas: (nombre, email,telefono,maestro, folio, vigencia, url_foto , asignar grado(kyu), asignar grupo, asignar materia/curso, escuela, CV, permitir acceso a contenido por alumnos)
- Procesos: (ABC alumnos, ABC calificaciones, ABC cursos, ABC contenido)
- Salidas: Salidas: usuario(email), password acceso al Sistema rol maestro.
- Restricciones: un maestro puede dar clases en varias clases, un maestro puede dar clases de varias materias/cursos. El maestro podra ver sus alumnos filtrados por grado y curso para asignar o quitar acceso(ver imagen panel de signación de contenido)
Módulo: (ABC admin)
- Objetivo: gestionar escuelas, alumnos, maestros, cursos, materias, contenido. Gestiona todo lo relacionado con la escuela.
- Entradas: 
-- ABC escuela: nombre, dirección, status
-- ABc alumnos
-- ABC maestos
-- ABC cursos: nombre del curso, temas, objetivo curso, objetivo temas, contenido(imagenes, pdf, texto, enlases, video de youtube, 
-- ABC grados: nombre,status (Kyu empieza en 10, 9,8…hasta 1dan luego sigue en 1dan, 2dan, 3dan…)

- Procesos: (ABC alumnos, ABC calificaciones, ABC cursos, ABC contenido)
- Salidas: (Gestion de toda la escuela y poder ver contenido así como aprobar cursos y temarios)
- Restricciones: (crear popups y alertas antes de hacer borrados o actualizaciones ya que afecta a todo el sistma)
Módulo: (ABC cursos)
- Objetivo: GEstionar cursos por parte del maestro así como la gestion de contenidos por curso
- Entradas: nombre, fecha de inicio, status
- Procesos: ABC cursos
- Salidas: mostrar cursos
- Restricciones: no borrar si hay contenido asociado o alertar que si seborran los curso se borran en cascada los contenidos, asignar contenidos a otros cursos en caso de borrado pero querer conserver los temarios.
Módulo: (ABC Contenido)
- Objetivo: gestionar contenidos en formato: texto, imagen, pdf, video de youtube, vimeo.
- Entradas: curso, grado o kyu, titulo, contenido status.
- Procesos: guardar para ser consultados por los alumnos siempre y cuando el maestro lo asigne
- Salidas: (resultado)
- Restricciones: El contenido generado se debe asignar a un curso, asignado a un kyu. Listo para se seleccionado por el maestro y ser asignado a alumno.
- Asignación de contenido: desde el modulo de maestro se listara por curso>grado los alumnos, y por cada alumno se peude asignar uno o varios contenidos(puede ser un popup donde se listen los contenidos por titulo, y switch on/off)
  
Módulo: (ABC Grados)
- Objetivo: gestión de grados Kyus
- Entradas: nombre, status
- Procesos: se gestionar los grados pueden ser: kyu 10, Kyu9, o curso por lo que puede ser tun texto, si se puede similar una cinta o agregar icono de cinta de karate de varios colores(opcional)
- Salidas: mostrar grado para ser asignado a un alumno y maestro
- Restricciones: no borrar en cascada
Módulo: (ABC grupos)
- Objetivo: gestionar y ordenar a los alumnos y maestros en grupo por curso
- Entradas: nombre grupo, curso, maestro, alumno
- Procesos: agrupar a los alumnos por grupo
- Salidas: nombre de los curso
- Restricciones: no borrar, solo cambiar status. Un alumno pude estar es varios grupos, un maestro puede estar en varios grupos, una escuela puede tener varios cursos y cada curso varios grupos

Módulo: (ABC calificaciones)
- Objetivo: el maestro o admin podrá gestionar calificaciones por alumno
- Entradas: id alumno, calificación por tema, calificación en(promedio) Kihon, kata, kumitachi, tate, reiho, teoría.
- Procesos: cada  curso tiene un tiempo de 4 meses aunque puede cambiar dependiendo del periodo, cada mes debe guardar (Kihon, kata, kumitachi, tate, reiho, teoría), al final de cada cuatrimestre debe generar un promedio general del curso terminado
- Salidas: el alumno debe poder visualizar sus calificaciones y avance
- Restricciones: el terminar un cuatrimestre solo lo prepara para el siguiente kyu o grado.

Módulo: (ver calificaciones)
- Objetivo: Sección de la vista de alumno, donde podrá ver sus calificaciones, por cuatrimestre en promedio general por kyu o grado, detalle de cada aspecto de calificación
- Entradas: (login de alumno, curso, grupo, grado)
- Procesos: (mostrar promedio por curso completado, mostrar promedio por grado completado, ver calificación por mes o por tema, detalle del temario calificación)
- Salidas: (vista con la consulta)
- Restricciones: (solo muestra la calificación del alumno logueado no del grupo )
Módulo: (ver progreso)
- Objetivo: el alumno podrá ver de forma gráfica su avance con estadísticas
- Entradas: id del alumno
- Procesos:  mostrar avances
- Salidas: 
- Restricciones: estos datos son personales por alumno logueado 
- Otra vista es la rendimiento puede ser una spider graph las calificaciones deben estar 1-10 y los criterios a mostrar en graficas son (Kihon, kata, kumitachi, tate, reiho, teoría)
  
Módulo: (ver credencial virtual)
- Objetivo:mostrar en el sistema una versión simple de una credencial
- Entradas: id_alumno
- Procesos: : foto, nombre fecha de inscripcion, Codigo QR(del folio)  folio
- Salidas: versión simple de un credencial el Qr dede ser claro
- Restricciones: por alumno logueado
 
Módulo: (ABC Perfil)
Cada usuario podrá editar sus propios datos personales: nombre, apellido paterno, nombre, cambio de contraseña. La foto la asigna se asigna en el alta o registro. Ver proceso de registro y su base datos.


Módulo: (ver avisos y promociones)
- Objetivo:el alumno podrá ver en su dasboard inciar una seir de aviso simples no invasivos.
- Entradas: id_promo o id_aviso activo 
- Procesos: mostrar en fora de lista 
- Salidas: 
 
- Restricciones: 
El admin o maestro pueden publicar avisos de tipo texto ( si esposible crear notificaciones push web, recordar que es una aplicación pero ver si podemos mandar notificaiones push gratis)

Módulo: (modulo de recuperación de contraseña)
- Objetivo: en caso de olividar la contraseña poder regerarla 
- Entradas: folio, correo electronico 
- Procesos: se proporciona folio y correo, si coinciden ambos se enviara un correo con la liga para cambiar la contraseña, al pulsar el correo se enviará a la vista para cambiar (actualzar la contraseña)
- Salidas: nueva contraseña
- Restricciones: sin restricciones.




Otros:
Considera que sea un sistema seguro contra inyección de código
carpeta de cintas: img/cintas/blanca.jpg, img/cintas/amarilla.jpg,  img/cintas/roja.jpg , img/cintas/verde.jpg, img/cintas/azul.jpg, img/cintas/roja.jpg


4. Flujo de navegación
Dibuja o describe cómo se mueven los usuarios dentro del sistema.
Ejemplo:

Login
- Admin
-- ABC maestros
-- ABC grupos
-- ABC grados
-- ABC escuela
- Maestro
-- ABC calificaciones
-- ABC contenido
-- Ver alumnos
-- Asignar contenidos
-- ABC promover grados (siguiente kyu o incipción al siguiente nivel)
- Alumno
-- dashboard, avisos, promos
-- ver calificaciones
-- ver contenido
-- ver avance
-- mi perfil
- Recuperar contraseña

5. Reglas de negocio
Usar datos del sistema de Registro y alimentar el sistema de alumnos.
Lista clara de condiciones que el sistema debe cumplir sí o sí.
Ejemplos:
- Un alumno solo puede acceder a katas según su grado.
- Un maestro puede asignar grados, pero no eliminar alumnos.
- El email debe ser único.
- El folio se genera en el sistema de registro
7. Diseño y UX/UI
- Colores: 
- para maestros detalles de header de menu negro(:#0a0907) con colores blancos (#fff) y borde dorado(#c8b052)
-para Admin: detalles del header en blanco(#ffffff) con borde dorado dorado(#c8b052)
- Para el alumno: header en rojo(#ae0304)con letras en blanco(#ffffff)
Colores:
Principal: #0a0907
Secundario: dorado(#c8b052), rojo(#ae0304)
Acentos: gris obscuro #d9d9d9, gris más claro #b2afaf
Colores de texto: negro: #000,  blanco: #fff


- Logo: https://elcaminodelaespada.com/wp-content/uploads/2024/11/elcaminodelaespada-clases-de-kenjutsu-150x150.png
- Tipografía: texto: AR One Sans, sanserif  para, titulos : Source serif pro
- Framework CSS: (Bootstrap, Tailwind…)
- Si es posible usar: https://github.com/startbootstrap/startbootstrap-sb-admin-2

8. Consideraciones técnicas
- Lenguaje y versión: PHP 8+
- Servidor: Apache o nginx 8server en hostinger)
- Base de datos: Mysql o maria DB
- Compatibilidad subcarpetas: Sí
- Otros: 
9. Datos de prueba
Incluye datos que podamos usar para testear:
- Usuarios iniciales :
-- admin : luzalcuadrado@gmail.com pass: F22b51a380*
-- maestro: antonio.luz@elcaminodelaespada.com pass: F22b51a380*
-- alumno: herramientas.elcamino@gmail.com pass: F22b51a380*

- kyus y contenido por kyu
Kyu	#	Kata	Descripción	URL
10	1	Mae (前) 	Corte frontal desde posición sentada (seiza).	https://youtu.be/BzEaGH4nfuI

10	2	Ushiro (後)	Corte hacia atrás desde seiza.	https://youtu.be/7i-p-5M9sLM

10	3	Ukenagashi (受け流し) 	Desvío y contraataque desde seiza.	https://youtu.be/YSZKuCa1DnE

10	4	Tsuka Ate (柄当て) 	Golpe con la tsuka (empuñadura) y corte.	https://youtu.be/St4-QjGTrUo

9	5	Kesa Giri (袈裟切り) 	Corte diagonal en movimiento.	https://youtu.be/4wAYC3hFJC0

9	6	Morote Zuki (諸手突き) 	Estocada con ambas manos.	https://youtu.be/Pny_6GhzvOc

9	7	Sanpō Giri (三方切り) 	Cortes a tres direcciones.	https://youtu.be/Qy7cjh4y_os

9	8	Ganmen Ate (眼面当て) 	Golpe al rostro y corte.	https://youtu.be/SpMcY0vF0V4

8	9	Sōgiri (総切り) 	Serie de cortes múltiples.	https://youtu.be/J1KrZkGRAM8

8	10	Shiho Giri (四方切り) 	Cortes a cuatro direcciones.	https://youtu.be/j_RxsNmT0e4

8	11	Sōete Zuki (添え手突き) 	Estocada con mano de apoyo.	https://youtu.be/jc2UftJsuhc

8	12	Nukiuchi (抜打ち) 	Desenvainado rápido y corte inmediato.	https://youtu.be/_MNdxqXo7QE

				
				
				



Otras consideraciones:
- Tengo un sistema de registro:
-- formulario de registro de datos: nombre,email, teléfono, foto, asignación del maestro, actual, folio(formato: aa(2 digitos del año)+mm(2 digitos de mes)+dd(2 digitos del dia)+(numero random del-0-99))
Ver : u611052736_registro.sql
- Fotos de alumnos del sistema:
https://elcaminodelaespada.com/control-escolar/registro/uploads/202506151119.png
la url del hosting: https://elcaminodelaespada.com/
url de control escolar: control-escolar/registro
carpeta donde se guardan las fotos: /uploads
foto formato: folio+extencion(.png) 

 

<img width="432" height="578" alt="image" src="https://github.com/user-attachments/assets/2daaf461-8ae6-4220-8c02-d0f56d3c09f8" />
