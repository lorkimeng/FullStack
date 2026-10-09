<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Users</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <router-link :to="{ name: 'dashboard' }"
                                    >Home</router-link
                                >
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title my-auto">User Management</h3>
                            <div class="d-flex justify-content-end">
                                <div class="card-tools">
                                    <div class="input-group input-group">
                                        <input
                                            v-model="keyword"
                                            type="text"
                                            class="form-control float-right"
                                            placeholder="Search"
                                        />
                                        <div class="input-group-append">
                                            <button
                                                class="btn btn-default"
                                                type="button"
                                            >
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <button
                                                class="btn btn-success"
                                                type="button"
                                                @click="showModal"
                                            >
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body table-responsive p-0">
                        <table
                            class="text-nowrap table-head-fixed table-valign-middle table table-head-fixed table-bordered table-hover"
                        >
                            <thead class="text-center">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="user in users"
                                    :key="user.id"
                                    class="text-center"
                                >
                                    <td>{{ user.id }}</td>
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td>{{ user.level }}</td>
                                    <td>
                                        <button
                                            class="mx-1 btn btn-sm btn-primary"
                                            @click="viewUser(user.id)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            class="mx-1 btn btn-sm btn-danger"
                                            @click="removeUser(user.id)"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer clearfix">
                        <div class="row">
                            <div class="col-md text-nowrap mb-2">
                                <div class="d-flex justify-content-between">
                                    <div class="col-auto my-auto">
                                        <span
                                            >Page {{ currentPage }} of
                                            {{ lastPage }} - {{ total }}
                                            {{
                                                total !== 1
                                                    ? "results"
                                                    : "result"
                                            }}</span
                                        >
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group input-group">
                                            <div class="input-group-prepend">
                                                <button class="btn btn-default">
                                                    Show
                                                </button>
                                            </div>
                                            <select
                                                v-model="pageSize"
                                                class="form-control"
                                            >
                                                <option
                                                    v-for="size in [
                                                        10, 25, 50, 100, 250,
                                                    ]"
                                                    :key="size"
                                                    :value="size"
                                                >
                                                    {{ size }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="d-flex justify-content-center">
                                    <div
                                        class="dataTables_paginate paging_simple_numbers"
                                    >
                                        <ul class="pagination">
                                            <!-- First page -->
                                            <li
                                                class="paginate_button page-item"
                                                :class="{
                                                    disabled: currentPage === 1,
                                                }"
                                            >
                                                <a
                                                    @click.prevent="
                                                        currentPage > 1 &&
                                                        changePage(1)
                                                    "
                                                    role="button"
                                                    tabindex="0"
                                                    class="page-link"
                                                    :style="{
                                                        cursor:
                                                            currentPage === 1
                                                                ? 'not-allowed'
                                                                : 'pointer',
                                                    }"
                                                >
                                                    <i
                                                        class="fas fa-angle-double-left"
                                                    ></i>
                                                </a>
                                            </li>
                                            <!-- Previous page -->
                                            <li
                                                class="paginate_button page-item"
                                                :class="{
                                                    disabled: currentPage === 1,
                                                }"
                                            >
                                                <a
                                                    @click.prevent="
                                                        currentPage > 1 &&
                                                        changePage(
                                                            currentPage - 1,
                                                        )
                                                    "
                                                    role="button"
                                                    tabindex="0"
                                                    class="page-link"
                                                    :style="{
                                                        cursor:
                                                            currentPage === 1
                                                                ? 'not-allowed'
                                                                : 'pointer',
                                                    }"
                                                >
                                                    <i
                                                        class="fas fa-angle-left"
                                                    ></i>
                                                </a>
                                            </li>

                                            <!-- Ellipsis before -->
                                            <li
                                                v-if="currentPage > 3"
                                                class="paginate_button page-item"
                                            >
                                                <a
                                                    class="page-link"
                                                    style="cursor: default"
                                                    >...</a
                                                >
                                            </li>

                                            <!-- Page numbers -->
                                            <template
                                                v-for="pageNum in lastPage"
                                                :key="pageNum"
                                            >
                                                <li
                                                    v-if="
                                                        pageNum >=
                                                            currentPage - 3 &&
                                                        pageNum <=
                                                            currentPage + 3
                                                    "
                                                    class="paginate_button page-item"
                                                    :class="{
                                                        active:
                                                            pageNum ===
                                                            currentPage,
                                                    }"
                                                >
                                                    <a
                                                        @click.prevent="
                                                            changePage(pageNum)
                                                        "
                                                        role="button"
                                                        tabindex="0"
                                                        class="page-link"
                                                        style="cursor: pointer"
                                                        >{{ pageNum }}</a
                                                    >
                                                </li>
                                            </template>

                                            <!-- Ellipsis after -->
                                            <li
                                                v-if="
                                                    currentPage < lastPage - 3
                                                "
                                                class="paginate_button page-item"
                                            >
                                                <a
                                                    class="page-link"
                                                    style="cursor: default"
                                                    >...</a
                                                >
                                            </li>

                                            <!-- Next page -->
                                            <li
                                                class="paginate_button page-item"
                                                :class="{
                                                    disabled:
                                                        currentPage ===
                                                        lastPage,
                                                }"
                                            >
                                                <a
                                                    @click.prevent="
                                                        currentPage <
                                                            lastPage &&
                                                        changePage(
                                                            currentPage + 1,
                                                        )
                                                    "
                                                    role="button"
                                                    tabindex="0"
                                                    class="page-link"
                                                    :style="{
                                                        cursor:
                                                            currentPage ===
                                                            lastPage
                                                                ? 'not-allowed'
                                                                : 'pointer',
                                                    }"
                                                >
                                                    <i
                                                        class="fas fa-angle-right"
                                                    ></i>
                                                </a>
                                            </li>
                                            <!-- Last page -->
                                            <li
                                                class="paginate_button page-item"
                                                :class="{
                                                    disabled:
                                                        currentPage ===
                                                        lastPage,
                                                }"
                                            >
                                                <a
                                                    @click.prevent="
                                                        currentPage <
                                                            lastPage &&
                                                        changePage(lastPage)
                                                    "
                                                    role="button"
                                                    tabindex="0"
                                                    class="page-link"
                                                    :style="{
                                                        cursor:
                                                            currentPage ===
                                                            lastPage
                                                                ? 'not-allowed'
                                                                : 'pointer',
                                                    }"
                                                >
                                                    <i
                                                        class="fas fa-angle-double-right"
                                                    ></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade" ref="userModal" aria-modal="true" role="dialog">
        <form @submit.prevent="saveUser">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">User</h4>
                        <button
                            type="button"
                            class="close"
                            @click="hideModal"
                            aria-label="Close"
                        >
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="userName">Name</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="user.name"
                                :class="{ 'is-invalid': !!userError.name }"
                            />
                            <div class="invalid-feedback">
                                {{ userError.name }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="userEmail">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                v-model="user.email"
                                :class="{ 'is-invalid': !!userError.email }"
                            />
                            <div class="invalid-feedback">
                                {{ userError.email }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="userPassword">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                v-model="user.password"
                                :class="{ 'is-invalid': !!userError.password }"
                            />
                            <div class="invalid-feedback">
                                {{ userError.password }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button
                            type="button"
                            class="btn btn-default"
                            @click="hideModal"
                        >
                            Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Save changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import $ from "jquery";
import Swal from "sweetalert2";
import {
    apiGetUsers,
    apiCreateUser,
    apiUpdateUser,
    apiReadUser,
    apiDeleteUser,
} from "@/functions/api/user";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { onMounted, ref, reactive, watch } from "vue";

const userModal = ref(null);
const users = ref([]);

// Pagination state
const currentPage = ref(1);
const pageSize = ref(25);
const total = ref(0);
const lastPage = ref(1);
const keyword = ref(""); // Track search keyword
const searchTimeout = ref(null);

const user = reactive({
    id: null,
    name: "",
    email: "",
    password: "",
});

const userError = reactive({
    name: "",
    email: "",
    password: "",
});

const defaultUser = JSON.parse(JSON.stringify(user));
const defaultUserError = JSON.parse(JSON.stringify(userError));

function resetAllState() {
    Object.assign(user, defaultUser);
    Object.assign(userError, defaultUserError);
}

onMounted(async () => {
    $(userModal.value).on("hide.bs.modal", function () {
        resetAllState();
    });
    try {
        LoadingModal();
        await generateUsers(keyword.value, currentPage.value, pageSize.value);
        return CloseModal();
    } catch (error) {
        return MessageModal({
            icon: "error",
            title: "Error",
            text: error.response?.data?.message || error.message,
        });
    }
});

// Watch for pagination changes to fetch data
watch(currentPage, async (newPage, oldPage) => {
    if (newPage !== oldPage) {
        await generateUsers(keyword.value, newPage, pageSize.value);
    }
});

watch(pageSize, async (newPageSize, oldPageSize) => {
    if (newPageSize !== oldPageSize) {
        await generateUsers(keyword.value, 1, newPageSize);
    }
});

watch(keyword, (newKeyword, oldKeyword) => {
    if (newKeyword !== oldKeyword) {
        if (searchTimeout.value) {
            clearTimeout(searchTimeout.value);
        }
        searchTimeout.value = setTimeout(async () => {
            await generateUsers(newKeyword, 1, pageSize.value);
        }, 500); // Debounce delay of 300ms
    }
});

async function generateUsers(searchKeyword, page, per_page) {
    try {
        LoadingModal();
        const response = await apiGetUsers({
            keyword: searchKeyword,
            page: page,
            per_page: per_page,
        });

        // Update all pagination state from API response
        users.value = response.data.users;
        currentPage.value = response.data.meta.current_page;
        pageSize.value = response.data.meta.per_page;
        total.value = response.data.meta.total;
        lastPage.value = response.data.meta.last_page;
        CloseModal();
    } catch (error) {
        MessageModal({
            icon: "error",
            title: "Error",
            text: error.response?.data?.message || error.message,
        });
    }
}

async function saveUser() {
    try {
        LoadingModal();
        let response = null;
        if (user.id) {
            response = await apiUpdateUser(user.id, user);
            onUserUpdate(response.data.user);
        } else {
            response = await apiCreateUser(user);
            onUserCreate(response.data.user);
        }

        // Implement save user logic here
        hideModal();
        return MessageModal({
            icon: "success",
            title: "Success",
            text: response.data.message,
        });
    } catch (error) {
        const { response } = error;
        if (!response) {
            return MessageModal({
                icon: "error",
                title: "Error",
                text: error.message,
            });
        }
        const { status, data } = response;
        if (status === 422) {
            Object.keys(userError).forEach((key) => {
                userError[key] = data.errors[key] ? data.errors[key][0] : "";
            });
            return CloseModal();
        }
        return MessageModal({
            icon: "error",
            title: "Error",
            text: data.message,
        });
    }
}

async function viewUser(id) {
    try {
        LoadingModal();
        const response = await apiReadUser(id);
        Object.assign(user, response.data.user);
        showModal();
        return CloseModal();
    } catch (error) {
        return MessageModal({
            icon: "error",
            title: "Error",
            text: error.response?.data?.message || error.message,
        });
    }
}

async function removeUser(id) {
    Swal.fire({
        icon: "warning",
        title: "Delete User",
        text: "Are you sure you want to delete this user? This action cannot be undone.",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                LoadingModal();
                const response = await apiDeleteUser(id);
                onUserDelete(id);
                return MessageModal({
                    icon: "success",
                    title: "Success",
                    text: response.data.message,
                });
            } catch (error) {
                return MessageModal({
                    icon: "error",
                    title: "Error",
                    text: error.response?.data?.message || error.message,
                });
            }
        }
    });
}

function showModal() {
    $(userModal.value).modal("show");
}
function hideModal() {
    $(userModal.value).modal("hide");
}

function onUserCreate(user) {
    users.value = [user, ...users.value];
}
function onUserUpdate(user) {
    users.value = users.value.map((u) => (u.id === user.id ? user : u));
}
function onUserDelete(id) {
    users.value = users.value.filter((u) => u.id !== id);
}

function changePage(pageNum) {
    currentPage.value = pageNum;
}
</script>
