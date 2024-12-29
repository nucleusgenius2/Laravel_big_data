import { createRouter, createWebHistory } from "vue-router";
import {authRequest} from "@/api.js";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/",
            name: "Home",
            component: () => import("@/pages/HomePage.vue"),
            meta: {
                layout : "mainLayout"
            }
        },
    ],
});



// protect router
router.beforeEach( async (to, from, next) => {
    if ( to.name === 'admin' || to.name === 'Profile' ) {
        if ( localStorage.getItem("token") !== null ) {

            let response = await authRequest('/api/authorization', 'get');

            if (to.name === 'admin') {
                if (response.data.permission === 'admin') {
                    next()
                } else {
                    next({name: 'Login'})
                }
            }

            if (to.name === 'Profile') {
                if (response.data.permission === 'user' || response.data.permission === 'admin') {
                    next()
                } else {
                    next({name: 'Login'})
                }
            }
        }
        else {
            next({name: 'Login'})
        }
    }
    else {
        next();
    }
})

export default router;
