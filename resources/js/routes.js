const Home = () => import('./components/Home.vue');
const Contacto = () => import('./components/Contacto.vue');

//importamos los componentes para el categoria
const Mostrar = () => import('./components/categoria_productos/Mostrar.vue');
const Crear = () => import('./components/categoria_productos/Crear.vue');
const Editar = () => import('./components/categoria_productos/Editar.vue');

export const routes = [
    {
        name: 'home',
        path: '/',
        component: Home
    },
    {
        name: 'contacto',
        path: '/contacto',
        component: Contacto
    },
    {
        name: 'mostrarCategoriasProductos',
        path: '/categorias_productos',
        component: Mostrar
    },
    {
        name: 'crearCategoriaProductos',
        path: '/crear',
        component: Crear
    },
    {
        name: 'editarCategoriaProductos',
        path: '/editar/:id',
        component: Editar
    }
];