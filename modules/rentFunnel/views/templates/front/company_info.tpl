<section class="company-info-home-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="company-info-card">
                    <div class="card-header">
                        <h2>Configurez votre solution café sur mesure</h2>
                        <p class="subtitle">Répondez à quelques questions pour commencer</p>
                    </div>

                    <div class="card-body">
                        {if isset($form_errors) && $form_errors}
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    {foreach $form_errors as $error}
                                        <li>{$error}</li>
                                    {/foreach}
                                </ul>
                            </div>
                        {/if}

                        <form action="{$urls.pages.index}" method="post" class="company-info-form" id="companyInfoForm">
                            <div class="main-questions">
                                <h3 class="section-title">Informations principales</h3>
                                <div class="questions-grid">
                                    {foreach from=$mainQuestions item=question}
                                        <div class="form-group">
                                            <label for="{$question.name}" class="form-label">
                                                {$question.label} <span class="required">*</span>
                                                {if $question.name=='additional_drinks'}
                                                    <br><small class="small">Ctrl+click pour sélectionner plusieurs options</small>
                                                {/if}
                                            </label>
                                            <select name="{$question.name}" id="{$question.name}" class="form-control custom-select" required {if $question.name=='additional_drinks'}multiple{/if}>
                                                <option value="">-- Sélectionnez --</option>
                                                {foreach from=$question.options item=option}
                                                    <option value="{$option|escape:'html':'UTF-8'}">
                                                        {$option|escape:'html':'UTF-8'}
                                                    </option>
                                                {/foreach}
                                            </select>
                                        </div>
                                        <br>
                                    {/foreach}
                                </div>
                            </div>

                            {if $dynamicQuestions && count($dynamicQuestions) > 0}
                                <div class="dynamic-questions">
                                    <h3>Informations complémentaires</h3>
                                    <div class="questions-grid">
                                        {foreach from=$dynamicQuestions item=dynQuestion}
                                            <div class="form-group dynamic-question-group">
                                                <label class="form-label dynamic-label">
                                                    {$dynQuestion.label|escape:'html':'UTF-8'}
                                                </label>

                                                <div class="dynamic-select-wrapper">
                                                    <select name="{$dynQuestion.select_name}"
                                                            id="{$dynQuestion.select_name}"
                                                            class="form-control custom-select dynamic-select">
                                                        <option value="">-- Sélectionnez une option --</option>
                                                        {foreach from=$dynQuestion.sub_categories item=subCat}
                                                            <option value="{$subCat.id}">
                                                                {$subCat.name|escape:'html':'UTF-8'}
                                                            </option>
                                                        {/foreach}
                                                    </select>

                                                    <small class="form-text text-muted">
                                                        Type de question : {$dynQuestion.question_type_label|escape:'html':'UTF-8'}
                                                    </small>
                                                </div>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            {/if}

                            <div class="form-actions">
                                <button type="submit" name="submitCompanyInfo" class="btn btn-primary btn-lg">
                                    <span>Commencer ma sélection</span>
                                    <i class="material-icons">arrow_forward</i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>