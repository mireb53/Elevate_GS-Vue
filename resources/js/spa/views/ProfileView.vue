<template>
  <div class="container py-4">
    <h3 class="mb-3">Profile</h3>
    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input v-model="profile.name" type="text" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input v-model="profile.email" type="email" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label">Role</label>
              <input :value="profile.role" type="text" class="form-control" disabled />
            </div>
            <button class="btn btn-primary" @click="save">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const profile = reactive({
  name: localStorage.getItem('loggedInUserName') || '',
  email: localStorage.getItem('loggedInUserEmail') || '',
  role: localStorage.getItem('loggedInUserRole') || 'student'
})

function save() {
  localStorage.setItem('loggedInUserName', profile.name)
  localStorage.setItem('loggedInUserEmail', profile.email)
  alert('Saved locally. Hook API to persist to backend.')
}
</script>
