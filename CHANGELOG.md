## [v1.1] - 2026-05-25

### Otros cambios
- Corrige carga de block_base en tests: require moodleblock.class.php directamente
- Corrige errores de codestyle, añade tests PHPUnit y hook pre-push
- Añade workflow de auto-bump de versión en cada push a main
- Añade CI/CD: auto-release, release y CI con syntax check y codestyle

## [v1.2.0] - 2026-07-22

### Nuevas funcionalidades

- añadir validaciones quizhasquestions, quizsinglepage y quizrandomquestions



## [v1.1.6] - 2026-07-14

### Otros cambios

- migrar a repo standalone con symlinks y tests con atributos PHPUnit 11



## [v1.1.5] - 2026-05-25

### Correcciones

- añadir --repo a gh release create para evitar error fuera del workspace



## [v1.1.4] - 2026-05-25

### Correcciones

- crear GitHub Release directamente en bump-version en lugar de depender de tag-push



## [v1.1.3] - 2026-05-25

### Correcciones

- move namespace before defined() in admin_setting_configdate
- namespace admin_setting_configdate to prevent double-load during PHPUnit init
- require admin_setting_configdate in settings.php for PHPUnit init



## [v1.1.2] - 2026-05-25

### Otros cambios

- Corrige brace de apertura de clase en test



## [v1.1.1] - 2026-05-25


- Nuevas validaciones, paginación y filtros en listado de cursos inválidos
- Refactoring, corrección de bugs y mejoras de validación
- resuelve errores cuando el nombre del profesor se repetia
- Los cursos ocultos no muestra el bloque y no se muestran los errores en el listado
- wip
- Se añade selector de fecha para los grupos
- wip
- wip
- wip
- Solo aparecen en resumen no validados los que tengan fecha inicio de un valor (feb)
- Los grupos tienen que estar creados despues de una fecha
- modificación en la vista de cursos no validados
- Arregla problema cuando falta cmid
- Validación de restricción del questionario. pledge completado
- Se añaden validaciones pledge
- añade botón para limpiar erroneos
- Algunos cambios en los strings
- Adaptado para segundo semestre 24-25
- Permite espacil al final en categoria Examen final
- wip
- Groups with idnumber planiexamenes
- Se acepta examen final  y con espacio al final para las subcategorias
- Add total invalidations
- Add export button on table
- fix block visiblity and some strings
- new build 2025011000
- adding all files from block validador
- Delete validation button added
- fix labelvalidation
- fix list quiz with 6 numbers for validate each one
- wip
- group name valid with 6 numbers
- Validation groups number. Valid with 1 group
- Some traductions changes
- It does not take uppercase and lowercase into account for the gradebook categories.
- Fix problema when course haven't groups
- List invalid courses pagination
- wip
- Add function has_config() for show settings
- Add config page
- Add gradebook validations
- wip
- Wip register on database and list invalid courses.
- add validation results to database
- Change expected_text
- Change expected_text
- Implement registration and updating of validation results in the database
- Fix warning when adding content
- Block visibility for teachers only
- wip
- wip
- wip
- wip
- wip
- Update string
- Inicialización del bloque validador


# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).
